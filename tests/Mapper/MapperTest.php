<?php

namespace Bdf\Prime\Mapper;

use _files\TestClock;
use Bdf\Prime\Behaviors\Behavior;
use Bdf\Prime\Bench\HydratorGeneration;
use Bdf\Prime\Clock\ClockAwareInterface;
use Bdf\Prime\Customer;
use Bdf\Prime\CustomerCriteria;
use Bdf\Prime\CustomerMapper;
use Bdf\Prime\CustomerPack;
use Bdf\Prime\Entity\Criteria;
use Bdf\Prime\Entity\Hydrator\HydratorGeneratedInterface;
use Bdf\Prime\Entity\Hydrator\MapperHydrator;
use Bdf\Prime\Entity\Hydrator\MapperHydratorInterface;
use Bdf\Prime\Exception\DBALException;
use Bdf\Prime\IdGenerators\AbstractGenerator;
use Bdf\Prime\IdGenerators\GeneratorInterface;
use Bdf\Prime\IdGenerators\GuidGenerator;
use Bdf\Prime\IdGenerators\NullGenerator;
use Bdf\Prime\IdGenerators\TableGenerator;
use Bdf\Prime\Mapper\Info\MapperInfo;
use Bdf\Prime\Pack;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Relations\Exceptions\RelationNotFoundException;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Prime\TestEmbeddedEntity;
use Bdf\Prime\TestEntity;
use Bdf\Prime\TestEntityMapper;
use Bdf\Prime\User;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

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
        $mapper->build();
        
        $this->assertEquals(TestEntity::class, $mapper->getEntityClass());
        $this->assertEquals(EntityRepository::class, $mapper->getRepositoryClass());
        $this->assertInstanceOf(Metadata::class, $mapper->metadata());
        $this->assertFalse($mapper->isReadOnly());
        $this->assertTrue($mapper->hasSchemaManager());
        $this->assertInstanceOf(MapperHydrator::class, $mapper->hydrator());
        $this->assertNull($mapper->allowUnknownAttribute());
    }
    
    /**
     * 
     */
    public function test_repository_class()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->build();
        
        $mapper->setRepositoryClass('Bdf\Prime\Repository\UnknownRepository');
        $this->assertEquals('Bdf\Prime\Repository\UnknownRepository', $mapper->getRepositoryClass());
    }

    /**
     *
     */
    public function test_info()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->build();

        $this->assertInstanceOf(MapperInfo::class, $mapper->info());
    }

    /**
     *
     */
    public function test_property_accessor_class()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->build();

        $mapper->setPropertyAccessorClass('UnknownHydrator');
        $this->assertEquals('UnknownHydrator', $mapper->getPropertyAccessorClass());
    }

    /**
     * 
     */
    public function test_read_only()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->build();
        
        $mapper->setReadOnly(true);
        $this->assertTrue($mapper->isReadOnly());
    }

    /**
     *
     */
    public function test_allow_unknown_attribute()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->build();

        $mapper->setAllowUnknownAttribute(true);
        $this->assertTrue($mapper->allowUnknownAttribute());
    }

    public function test_relation()
    {
        $mapper = new CustomerMapper(Prime::service(), Customer::class);
        $mapper->build();

        $this->assertEquals([
            'name' => 'users',
            'type' => 'hasMany',
            'localKey' => 'id',
            'entity' => User::class,
            'distantKey' => 'customer.id',
            'detached' => true,
        ], $mapper->relation('users'));

        $this->assertEquals([
            'name' => 'users',
            'type' => 'hasMany',
            'localKey' => 'id',
            'entity' => User::class,
            'distantKey' => 'customer.id',
            'detached' => true,
        ], $mapper->relation(User::class, 'users'));

        $this->assertEquals([
            'name' => 'webUsers',
            'type' => 'hasMany',
            'localKey' => 'id',
            'entity' => User::class,
            'distantKey' => 'customer.id',
            'detached' => true,
            'constraints' => ['faction.domain' => 'user'],
        ], $mapper->relation(User::class, 'webUsers'));

        $this->assertEquals([
            'name' => 'packs',
            'type' => 'belongsToMany',
            'localKey' => 'id',
            'entity' => Pack::class,
            'distantKey' => 'id',
            'through' => CustomerPack::class,
            'throughLocal' => 'customerId',
            'throughDistant' => 'packId',
        ], $mapper->relation(Pack::class));
    }

    public function test_relation_legacy()
    {
        $mapper = new LegacyMapper(Prime::service(), TestEntity::class);
        $mapper->build();

        $this->assertEquals([
            'type' => 'hasOne',
            'entity' => TestEmbeddedEntity::class,
            'localKey' => 'foreign.id',
            'distantKey' => 'id',
            'name' => 'foreign',
        ], $mapper->relation('foreign'));

        $this->assertEquals([
            'type' => 'hasOne',
            'entity' => TestEmbeddedEntity::class,
            'localKey' => 'foreign.id',
            'distantKey' => 'id',
            'name' => 'foreign',
        ], $mapper->relation(TestEmbeddedEntity::class));

        $this->assertEquals([
            'type' => 'hasOne',
            'entity' => TestEmbeddedEntity::class,
            'localKey' => 'foreign.id',
            'distantKey' => 'id',
            'name' => 'foreign',
        ], $mapper->relation(TestEmbeddedEntity::class, 'foreign'));
    }

    public function test_relation_not_found()
    {
        $this->expectException(RelationNotFoundException::class);
        $this->expectExceptionMessage('Relation "not_found" is not set in Bdf\Prime\Customer');

        $mapper = new CustomerMapper(Prime::service(), Customer::class);
        $mapper->build();

        $mapper->relation('not_found');
    }

    public function test_relation_class_ambiguous()
    {
        $this->expectException(RelationNotFoundException::class);
        $this->expectExceptionMessage('Multiple relations found for class "Bdf\Prime\User" in Bdf\Prime\Customer. Please specify the relation name (available relations: users, webUsers)');

        $mapper = new CustomerMapper(Prime::service(), Customer::class);
        $mapper->build();

        $mapper->relation(User::class);
    }

    public function test_relation_class_ambiguous_not_match_with_name()
    {
        $this->expectException(RelationNotFoundException::class);
        $this->expectExceptionMessage('Relation "location" is not set in Bdf\Prime\Customer or does not match the given class "Bdf\Prime\User"');

        $mapper = new CustomerMapper(Prime::service(), Customer::class);
        $mapper->build();

        $mapper->relation(User::class, 'location');
    }

    public function test_relation_with_class_invalid_name()
    {
        $this->expectException(RelationNotFoundException::class);
        $this->expectExceptionMessage('Relation "not_found" is not set in Bdf\Prime\Customer or does not match the given class "Bdf\Prime\User"');

        $mapper = new CustomerMapper(Prime::service(), Customer::class);
        $mapper->build();

        $mapper->relation(User::class, 'not_found');
    }

    /**
     * 
     */
    public function test_schema_manager()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->build();
        
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
        $mapper->build();
        
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
        $mapper->build();
        
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
        $mapper->build();
        
        $this->assertInstanceOf(TableGenerator::class, $mapper->generator());
    }

    /**
     * 
     */
    public function test_generator_defined_as_string()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->build();
        $mapper->setGenerator(GuidGenerator::class);
        
        $this->assertInstanceOf(GuidGenerator::class, $mapper->generator());
    }
    
    /**
     * 
     */
    public function test_set_generator()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->build();
        $mapper->setGenerator(new \Bdf\Prime\IdGenerators\GuidGenerator($mapper));
        
        $this->assertInstanceOf(GuidGenerator::class, $mapper->generator());
    }

    /**
     *
     */
    public function test_set_generator_clock_aware()
    {
        $generator = new class extends AbstractGenerator implements ClockAwareInterface {
            public $clock;

            public function setClock(ClockInterface $clock): void
            {
                $this->clock = $clock;
            }
        };

        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->setClock($clock = new TestClock());
        $mapper->build();
        $mapper->setGenerator($generator);

        $this->assertSame($generator, $mapper->generator());
        $this->assertSame($clock, $generator->clock);
    }

    /**
     *
     */
    public function test_set_generator_clockaware_without_clock()
    {
        $generator = new class extends AbstractGenerator implements ClockAwareInterface {
            public $clock;

            public function setClock(ClockInterface $clock): void
            {
                $this->clock = $clock;
            }
        };

        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->build();
        $mapper->setGenerator($generator);

        $this->assertSame($generator, $mapper->generator());
        $this->assertNull($generator->clock);
    }

    /**
     *
     */
    public function test_set_generator_classname_clock_aware()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->setClock($clock = new TestClock());
        $mapper->build();
        $mapper->setGenerator(MyClockAwareGenerator::class);

        $this->assertInstanceOf(MyClockAwareGenerator::class, $mapper->generator());
        $this->assertSame($clock, $mapper->generator()->clock);
    }

    /**
     *
     */
    public function test_set_generator_classname_clock_aware_without_clock()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->build();
        $mapper->setGenerator(MyClockAwareGenerator::class);

        $this->assertInstanceOf(MyClockAwareGenerator::class, $mapper->generator());
        $this->assertNull($mapper->generator()->clock);
    }

    /**
     *
     */
    public function test_set_hydrator()
    {
        $hydrator = new MapperHydrator();
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->setHydrator($hydrator);
        $mapper->build();

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
        $mapper->build();
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
        $mapper->build();

        
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
        $mapper->build();

        
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
        $mapper->build();

        
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
        $mapper->build();

        
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
        $mapper->build();

        
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
        $mapper->build();

        
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
        $mapper->build();

        
        $entity->id = 1;
        $this->assertEquals(['id' => 1], $mapper->primaryCriteria($entity));
    }
    
    /**
     *
     */
    public function test_instantiate()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);
        $mapper->build();

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
        $mapper->build();

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
        $mapper->build();

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
        $mapper->build();

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
        $mapper->build();

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
        $mapper->build();
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
        $mapper->build();
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
        $mapper->build();
        $mapper->prepareFromRepository($data, Prime::connection('test')->platform(), $optimisation);

        $this->assertSame([], $optimisation['relations']['foreign.id']);
    }

    public function test_with_custom_criteria()
    {
        $mapper = new TestEntityMapper(Prime::service(), TestEntity::class);

        $this->assertSame(Criteria::class, get_class($mapper->criteria()));
        $this->assertEquals(new Criteria(['name' => 'foo']), $mapper->criteria(['name' => 'foo']));

        $mapper->setCriteriaClass(MyCustomCriteria::class);
        $this->assertSame(MyCustomCriteria::class, get_class($mapper->criteria()));
        $this->assertEquals(new MyCustomCriteria(['name' => 'foo']), $mapper->criteria(['name' => 'foo']));
    }

    public function test_dedicated_criteria()
    {
        $mapper = new CustomerMapper(Prime::service(), Customer::class);

        $this->assertSame(CustomerCriteria::class, get_class($mapper->criteria()));
    }

    public function test_with_clockaware_behavior()
    {
        $mapper = new MapperWithClockAwareBehavior(Prime::service(), TestEntity::class);
        $this->assertNull($mapper->behaviors()[0]->clock);

        $mapper = new MapperWithClockAwareBehavior(Prime::service(), TestEntity::class);
        $mapper->setClock($clock = new TestClock());
        $this->assertSame($clock, $mapper->behaviors()[0]->clock);
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

class MyCustomCriteria extends Criteria
{
    public function foo(string $bar): self
    {
        $this->add('name', 'foo' . $bar);

        return $this;
    }
}

class LegacyMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'database'   => 'test',
            'table'      => 'test_',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function fields(): iterable
    {
        return [
            'id'          => ['type' => 'integer', 'primary' => Metadata::PK_AUTOINCREMENT],
            'name'        => ['type' => 'string', 'length' => '255'],
            'dateInsert'  => ['type' => 'datetime', 'alias' => 'date_insert', 'nillable' => true],
            'foreign'     => [
                'class'    => TestEmbeddedEntity::class,
                'embedded' => [
                    'id'    => ['type' => 'integer', 'alias' => 'foreign_key', 'nillable' => true],
                ]
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function relations(): array
    {
        return [
            'foreign' => [
                'type'       => 'hasOne',
                'entity'     => TestEmbeddedEntity::class,
                'localKey'   => 'foreign.id',
                'distantKey' => 'id',
            ]
        ];
    }
}

class MyClockAwareGenerator extends AbstractGenerator implements ClockAwareInterface
{
    public $clock;

    public function setClock(ClockInterface $clock): void
    {
        $this->clock = $clock;
    }
}

class MyClockAwareBehavior extends Behavior implements ClockAwareInterface
{
    public $clock;

    public function setClock(ClockInterface $clock): void
    {
        $this->clock = $clock;
    }
}

class MapperWithClockAwareBehavior extends TestEntityMapper
{
    public function getDefinedBehaviors(): array
    {
        return [
            new MyClockAwareBehavior(),
        ];
    }
}
