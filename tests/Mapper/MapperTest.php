<?php

namespace Bdf\Prime\Mapper;

use Bdf\Prime\Bench\HydratorGeneration;
use Bdf\Prime\Entity\Hydrator\HydratorGeneratedInterface;
use Bdf\Prime\Entity\Hydrator\MapperHydrator;
use Bdf\Prime\Entity\Hydrator\MapperHydratorInterface;
use Bdf\Prime\Exception\DBALException;
use Bdf\Prime\IdGenerators\GuidGenerator;
use Bdf\Prime\IdGenerators\NullGenerator;
use Bdf\Prime\IdGenerators\TableGenerator;
use Bdf\Prime\Mapper\Info\MapperInfo;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Prime\TestEntity;
use Bdf\Prime\TestEntityMapper;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class MapperTest extends TestCase
{
    use PrimeTestCase;
    use HydratorGeneration;

    /**
     * @var MapperHydratorInterface[]
     */
    protected $hydrators = [];

    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();

        $this->hydrators = [
            "default" => new MapperHydrator(),
            "generated" => $this->createGeneratedHydrator(TestEntity::class)
        ];
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->primeStop();
    }

    /**
     * 
     */
    public function test_default()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        
        $this->assertEquals(TestEntity::class, $mapper->getEntityClass());
        $this->assertEquals(EntityRepository::class, $mapper->getRepositoryClass());
        $this->assertInstanceOf(Metadata::class, $mapper->metadata());
        $this->assertFalse($mapper->isReadOnly());
        $this->assertTrue($mapper->hasSchemaManager());
        $this->assertInstanceOf(MapperHydrator::class, $mapper->hydrator());
    }
    
    /**
     * 
     */
    public function test_repository_class()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        
        $mapper->setRepositoryClass('Bdf\Prime\Repository\UnknownRepository');
        $this->assertEquals('Bdf\Prime\Repository\UnknownRepository', $mapper->getRepositoryClass());
    }

    /**
     *
     */
    public function test_info()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);

        $this->assertInstanceOf(MapperInfo::class, $mapper->info());
    }

    /**
     *
     */
    public function test_property_accessor_class()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);

        $mapper->setPropertyAccessorClass('UnknownHydrator');
        $this->assertEquals('UnknownHydrator', $mapper->getPropertyAccessorClass());
    }

    /**
     * 
     */
    public function test_read_only()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        
        $mapper->setReadOnly(true);
        $this->assertTrue($mapper->isReadOnly());
    }
    
    /**
     * 
     */
    public function test_schema_manager()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        
        $mapper->disableSchemaManager();
        $this->assertFalse($mapper->hasSchemaManager());
    }

    /**
     * 
     */
    public function test_generator_primary_has_no_definition()
    {
        $metadata = $this->createMock(Metadata::class);
        $metadata->expects($this->any())->method('isAutoIncrementPrimaryKey')->will($this->returnValue(false));
        $metadata->expects($this->any())->method('isSequencePrimaryKey')->will($this->returnValue(false));
        
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class, $metadata);
        
        $this->assertInstanceOf(NullGenerator::class, $mapper->generator());
    }

    /**
     * 
     */
    public function test_generator_autoincrement()
    {
        $metadata = $this->createMock(Metadata::class);
        $metadata->expects($this->any())->method('isAutoIncrementPrimaryKey')->will($this->returnValue(true));
        $metadata->expects($this->any())->method('isSequencePrimaryKey')->will($this->returnValue(false));
        
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class, $metadata);
        
        $this->assertInstanceOf('Bdf\Prime\IdGenerators\AutoincrementGenerator', $mapper->generator());
    }

    /**
     * 
     */
    public function test_generator_sequence()
    {
        $metadata = $this->createMock(Metadata::class);
        $metadata->expects($this->any())->method('isAutoIncrementPrimaryKey')->will($this->returnValue(false));
        $metadata->expects($this->any())->method('isSequencePrimaryKey')->will($this->returnValue(true));
        
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class, $metadata);
        
        $this->assertInstanceOf(TableGenerator::class, $mapper->generator());
    }

    /**
     * 
     */
    public function test_generator_defined_as_string()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->setGenerator(GuidGenerator::class);
        
        $this->assertInstanceOf(GuidGenerator::class, $mapper->generator());
    }
    
    /**
     * 
     */
    public function test_set_generator()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->setGenerator(new \Bdf\Prime\IdGenerators\GuidGenerator($mapper));
        
        $this->assertInstanceOf(GuidGenerator::class, $mapper->generator());
    }

    /**
     *
     */
    public function test_set_hydrator()
    {
        $hydrator = new MapperHydrator();
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->setHydrator($hydrator);

        $this->assertSame($hydrator, $mapper->hydrator());
    }
    
    /**
     * 
     */
    public function test_set_generator_need_valid_generator()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessageMatches('/Trying to set an invalid generator/');

        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->setGenerator(new TestEntity());
    }

    /**
     * @dataProvider provideHydrator
     */
    public function test_setId($hydrator)
    {
        $entity = new TestEntity();
        $mapper = new TestEntityMapper(Prime::service(), get_class($entity));
        $mapper->setHydrator($this->hydrators[$hydrator]);

        
        $mapper->setId($entity, 100);
        $this->assertEquals(100, $entity->id);
    }
    
    /**
     * @dataProvider provideHydrator
     */
    public function test_getId($hydrator)
    {
        $entity = new TestEntity();
        $mapper = new TestEntityMapper(Prime::service(), get_class($entity));
        $mapper->setHydrator($this->hydrators[$hydrator]);

        
        $entity->id = 99;
        $this->assertEquals(99, $mapper->getId($entity));
    }
    
    /**
     * @dataProvider provideHydrator
     */
    public function test_hydrateOne_on_simple_property($hydrator)
    {
        $entity = new TestEntity();
        $mapper = new TestEntityMapper(Prime::service(), get_class($entity));
        $mapper->setHydrator($this->hydrators[$hydrator]);

        
        $mapper->hydrateOne($entity, 'name', 'new name');
        $this->assertEquals('new name', $entity->name);
    }
    
    /**
     * @dataProvider provideHydrator
     */
    public function test_extractOne_on_simple_property($hydrator)
    {
        $entity = new TestEntity();
        $mapper = new TestEntityMapper(Prime::service(), get_class($entity));
        $mapper->setHydrator($this->hydrators[$hydrator]);

        
        $entity->name = 'new name';
        $this->assertEquals('new name', $mapper->extractOne($entity, 'name'));
    }
    
    /**
     * @dataProvider provideHydrator
     */
    public function test_hydrateOne_on_embedded_property($hydrator)
    {
        $entity = new TestEntity();
        $mapper = new TestEntityMapper(Prime::service(), get_class($entity));
        $mapper->setHydrator($this->hydrators[$hydrator]);

        
        $mapper->hydrateOne($entity, 'foreign.id', 'new id');
        $this->assertEquals('new id', $entity->foreign->id);
    }
    
    /**
     * @dataProvider provideHydrator
     */
    public function test_extractOne_on_embedded_property($hydrator)
    {
        $entity = new TestEntity();
        $mapper = new TestEntityMapper(Prime::service(), get_class($entity));
        $mapper->setHydrator($this->hydrators[$hydrator]);

        
        $entity->foreign->id = 'new id';
        $this->assertEquals('new id', $mapper->extractOne($entity, 'foreign.id'));
    }
    
    /**
     * @todo tester sur un entity avec plusieurs cles primaires
     *
     * @dataProvider provideHydrator
     */
    public function test_primaryCriteria($hydrator)
    {
        $entity = new TestEntity();
        $mapper = new TestEntityMapper(Prime::service(), get_class($entity));
        $mapper->setHydrator($this->hydrators[$hydrator]);

        
        $entity->id = 1;
        $this->assertEquals(['id' => 1], $mapper->primaryCriteria($entity));
    }
    
    /**
     *
     */
    public function test_instantiate()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);

        $this->assertInstanceOf(TestEntity::class, $mapper->instantiate());
    }

    /**
     * @todo tester un entity initializable
     *
     * @dataProvider provideHydrator
     */
    public function test_entity($hydrator)
    {
        $attributes = [
            'id'      => 2,
            'foreign' => ['id' => 3],
        ];

        $entity = new TestEntity($attributes);
        $mapper = new TestEntityMapper(Prime::service(), get_class($entity));
        $mapper->setHydrator($this->hydrators[$hydrator]);

        $this->assertEquals($entity, $mapper->entity($attributes));
    }

    /**
     * Return one dimension array with no formatted values
     *
     * @dataProvider provideHydrator
     */
    public function test_prepareToRepository_with_unvalid_nillable_attribute($hydrator)
    {
        $this->pack()->declareEntity(TestEntity::class);

        $entity = new TestEntity();

        try {
            $entity->save();
        } catch (DBALException $e) {
            $this->assertInstanceOf(NotNullConstraintViolationException::class, $e->getPrevious());
            return;
        }

        $this->fail('should throw dbal exception');
    }
    
    /**
     * @dataProvider provideHydrator
     */
    public function test_prepareToRepository_with_attribute($hydrator)
    {
        $entity = new TestEntity([
            'id'            => 2,
            'name'          => 'test',
            'foreign'       => ['id' => 3],
            'dateInsert'    => new \DateTime(),
        ]);
        
        $mapper = new TestEntityMapper(Prime::service(), get_class($entity));
        $mapper->setHydrator($this->hydrators[$hydrator]);

        $data = $mapper->prepareToRepository($entity, ['id' => true]);
        
        $this->assertEquals(1, count($data), 'count of exported data');
        $this->assertEquals($entity->id, $data['id'], 'id');
    }
    
    /**
     * @dataProvider provideHydrator
     */
    public function test_prepareToRepository($hydrator)
    {
        $entity = new TestEntity([
            'id'            => 2,
            'name'          => 'test',
            'foreign'       => ['id' => 3],
            'dateInsert'    => new \DateTime(),
        ]);
        
        $mapper = new TestEntityMapper(Prime::service(), get_class($entity));
        $mapper->setHydrator($this->hydrators[$hydrator]);

        $data = $mapper->prepareToRepository($entity);
        
        $this->assertEquals(4, count($data), 'count of exported data');
        
        $this->assertEquals($entity->id, $data['id'], 'id');
        $this->assertEquals($entity->name, $data['name'], 'name');
        $this->assertEquals($entity->foreign->id, $data['foreign.id'], 'foreign.id');
        $this->assertEquals($entity->dateInsert, $data['dateInsert'], 'dateInsert');
    }
    
    /**
     * @dataProvider provideHydrator
     */
    public function test_prepareToRepository_with_nillable_attribute($hydrator)
    {
        $entity = new TestEntity([
            'id'            => 2,
            'name'          => 'test',
            'dateInsert'    => new \DateTime(),
        ]);
        
        $mapper = new TestEntityMapper(Prime::service(), get_class($entity));
        $mapper->setHydrator($this->hydrators[$hydrator]);

        $data = $mapper->prepareToRepository($entity);
        
        $this->assertEquals(4, count($data), 'count of exported data');
        
        $this->assertEquals($entity->foreign->id, null, 'foreign.id');
    }
    
    /**
     * @todo tester: un attribut null qui est declaré avec un default
     * @todo tester: un attribut primary null autoincrement ne declenche pas d'exception
     * @todo tester: un attribut primary null non autoincrement declenche une exception
     */
    
    
    /**
     * @dataProvider provideHydrator
     */
    public function test_prepareFromRepository($hydrator)
    {
        $data = [
            'id'            => '2',
            'name'          => 'test',
            'foreign_key'   => '3',
            'date_insert'   => '2015-04-20 10:00:00',
        ];
        
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->setHydrator($this->hydrators[$hydrator]);
        $entity = $mapper->prepareFromRepository($data, Prime::connection('test')->platform());
        
        $this->assertSame(2, $entity->id, 'id');
        $this->assertSame('test', $entity->name, 'name');
        $this->assertSame(3, $entity->foreign->id, 'foreign.id');
        $this->assertSame('2015-04-20 10:00:00', $entity->dateInsert->format('Y-m-d H:i:s'), 'dateInsert');
    }
    
    /**
     * @dataProvider provideHydrator
     */
    public function test_prepareFromRepository_parse_only_known_fields($hydrator)
    {
        $data = [
            'id'            => '2',
            'unknown'       => 'test',
        ];
        
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->setHydrator($this->hydrators[$hydrator]);
        $entity = $mapper->prepareFromRepository($data, Prime::connection('test')->platform());
        
        $this->assertSame(2, $entity->id, 'id');
        $this->assertFalse(isset($entity->unknown), 'unknown');
    }

    /**
     * @dataProvider provideHydrator
     */
    public function test_prepareFromRepository_manage_empty_key_relation($hydrator)
    {
        $data = [
            'id'            => '2',
            'name'          => 'test',
            'foreign_key'   => null,
        ];

        $optimisation = [
            'relations' => ['foreign.id' => []],
        ];

        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->setHydrator($this->hydrators[$hydrator]);
        $mapper->prepareFromRepository($data, Prime::connection('test')->platform(), $optimisation);

        $this->assertSame([], $optimisation['relations']['foreign.id']);
    }

    /**
     * @return HydratorGeneratedInterface[]
     */
    public function provideHydrator()
    {
        return [
            ["default"],
            ["generated"]
        ];
    }
}
