<?php

namespace Bdf\Prime\Mapper;

use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Prime;
use Bdf\Prime\Relations\Builder\RelationBuilder;
use Bdf\Prime\Test\RepositoryAssertion;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 *
 */
class SimpleObjectMapperTest extends TestCase
{
    use RepositoryAssertion;
    
    /**
     * 
     */
    protected function setUp(): void
    {
        $this->getTestPack()
            ->declareEntity(__NAMESPACE__.'\SimpleEntity')
            ->declareEntity(__NAMESPACE__.'\SimpleEmbeddedEntity')
            ->initialize();
    }
    
    /**
     * 
     */
    protected function tearDown(): void
    {
        $this->getTestPack()->destroy();
    }
    
    /**
     * 
     */
    public function test_default()
    {
        $mapper = new SimpleEntityMapper(Prime::service(), __NAMESPACE__.'\SimpleEntity');
        $mapper->build();
        
        $this->assertEquals(__NAMESPACE__.'\SimpleEntity', $mapper->getEntityClass());
        $this->assertEquals('stdClass', $mapper->metadata()->entityClass);
    }
    
    /**
     * 
     */
    public function test_entity()
    {
        $mapper = new SimpleEntityMapper(Prime::service(), __NAMESPACE__.'\SimpleEntity');
        $mapper->build();
        
        $this->assertEquals(new stdClass, $mapper->entity(['id' => 1]));
    }
    
    /**
     * 
     */
    public function test_find()
    {
        $data = (object)[
            'id'         => 1,
            'name'       => __FUNCTION__,
            'dateInsert' => new \DateTime(),
            'foreign'    => (object)['id' => null],
        ];
        
        $repository = Prime::repository(__NAMESPACE__.'\SimpleEntity');
        $repository->insert($data);
        
        $entity = $repository->findOne([
            'id' => 1
        ]);

        $this->assertEquals($data, $entity);
    }
    
    /**
     * 
     */
    public function test_relation()
    {
        $simpleEntity = (object)[
            'id'         => 1,
            'name'       => __FUNCTION__,
            'dateInsert' => new \DateTime(),
            'foreign'    => (object)['id' => 10],
        ];
        $embededEntity = (object)[
            'id'         => 10,
            'name'       => __FUNCTION__,
            'city'       => 'here',
        ];
        
        Prime::push(__NAMESPACE__.'\SimpleEntity', $simpleEntity);
        Prime::push(__NAMESPACE__.'\SimpleEmbeddedEntity', $embededEntity);
        
        $entity = Prime::repository(__NAMESPACE__.'\SimpleEntity')->with('foreign')->findOne([
            'id' => 1
        ]);
        
        $simpleEntity->foreign = $embededEntity;

        $this->assertEquals($simpleEntity, $entity);
    }
    
    /**
     * 
     */
    public function test_relation_constraints()
    {
        Prime::push(__NAMESPACE__.'\SimpleEntity', (object)[
            'id'      => 1,
            'name'    => 'test',
            'foreign' => (object)['id' => 1],
        ]);
        
        Prime::push(__NAMESPACE__.'\SimpleEmbeddedEntity', (object)[
            'id'      => 1,
            'name'    => 'test-embedded',
        ]);
        
        $entity = Prime::repository(__NAMESPACE__.'\SimpleEntity')
            ->with(['foreign' => function($query){
                $query->where('name', ':like', 'test%');
            }])
            ->first();
        
        $this->assertEquals('test-embedded', $entity->foreign->name);
        
        $entity = Prime::repository(__NAMESPACE__.'\SimpleEntity')
            ->with(['foreign' => function($query){
                $query->where('name', ':like', 'test');
            }])
            ->first();
        
        $this->assertFalse(isset($entity->foreign->name));
    }
}



class SimpleEntityMapper extends Mapper
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
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->autoincrement()
            ->string('name')
            ->dateTime('dateInsert')->alias('date_insert')->nillable()
            ->embedded('foreign', __NAMESPACE__.'\SimpleEmbeddedEntity', function(FieldBuilder $builder){
                $builder->integer('id')->alias('foreign_key')->nillable();
            })
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        $builder->on('foreign')
            ->hasOne(__NAMESPACE__.'\SimpleEmbeddedEntity', 'foreign.id')
        ;
    }
}

class SimpleEmbeddedEntityMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'database' => 'test',
            'table' => 'foreign_',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->sequence()->alias('pk_id')
            ->string('name', 90)->alias('name_')
            ->string('city', 90)->nillable()
        ;
    }
}
