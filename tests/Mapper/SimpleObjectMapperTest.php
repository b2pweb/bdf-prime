<?php

namespace Bdf\Prime\Mapper;

use Bdf\Prime\Prime;
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
        
        $this->assertEquals(__NAMESPACE__.'\SimpleEntity', $mapper->getEntityClass());
        $this->assertEquals('stdClass', $mapper->metadata()->entityClass);
    }
    
    /**
     * 
     */
    public function test_entity()
    {
        $mapper = new SimpleEntityMapper(Prime::service(), __NAMESPACE__.'\SimpleEntity');
        
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
    public function fields(): iterable
    {
        return [
            'id'          => ['type' => 'integer', 'primary' => Metadata::PK_AUTOINCREMENT],
            'name'        => ['type' => 'string', 'length' => '255'],
            'dateInsert'  => ['type' => 'datetime', 'alias' => 'date_insert', 'nillable' => true],
            'foreign'     => [
                'class'    => __NAMESPACE__.'\SimpleEmbeddedEntity',
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
                'entity'     => __NAMESPACE__.'\SimpleEmbeddedEntity',
                'localKey'   => 'foreign.id',
                'distantKey' => 'id',
            ]
        ];
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
    public function fields(): iterable
    {
        return [
            'id'          => ['type' => 'integer', 'primary' => Metadata::PK_SEQUENCE, 'alias' => 'pk_id'],
            'name'        => ['type' => 'string', 'length' => '90', 'alias' => 'name_'],
            'city'        => ['type' => 'string', 'length' => '90', 'nillable' => true],
        ];
    }
}
