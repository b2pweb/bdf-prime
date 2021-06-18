<?php

namespace Bdf\Prime\Repository;

use Bdf\Prime\Events;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Right;
use Bdf\Prime\Test\RepositoryAssertion;
use Bdf\Prime\TestEntity;
use Bdf\Prime\TestEmbeddedEntity;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class EntityRepositoryTest extends TestCase
{
    use PrimeTestCase;
    use RepositoryAssertion;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();
    }

    /**
     *
     */
    protected function declareTestData($pack)
    {
        $pack
            ->declareEntity('Bdf\Prime\Faction')
            ->persist([
                'entity' => new TestEntity([
                    'id'         => 1,
                    'name'       => 'Entity',
                    'foreign'    => new TestEmbeddedEntity(['id' => 1]),
                    'dateInsert' => new \DateTime(),
                ]),
                'embedded' => new TestEmbeddedEntity([
                    'id'        => 1,
                    'name'      => 'Embedded',
                    'city'      => 'City',
                ]),
            ]);
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
    public function test_constraints()
    {
        $constraints = Prime::repository('Bdf\Prime\Faction')->constraints();

        $this->assertEquals(true, $constraints['enabled']);
    }

    /**
     *
     */
    public function test_disabled_constraints()
    {
        $repository = Prime::repository('Bdf\Prime\Faction');
        $repository->withoutConstraints();
        $constraints = $repository->constraints();

        $this->assertEquals([], $constraints);
    }

    /**
     *
     */
    public function test_constraints_with_context()
    {
        $constraints = Prime::repository('Bdf\Prime\Faction')->constraints('role');

        $this->assertEquals(true, $constraints['role.enabled']);
    }

    /**
     *
     */
    public function test_constraints_functionnal()
    {
        Prime::push('Bdf\Prime\Faction', [
            'id' => '1',
            'name' => 'group1',
        ]);
        Prime::push('Bdf\Prime\Faction', [
            'id' => '2',
            'name' => 'group2',
            'enabled' => false,
        ]);

        $repository = Prime::repository('Bdf\Prime\Faction');

        $this->assertEquals(1, $repository->count());
        $this->assertEquals(2, $repository->withoutConstraints()->count());
    }

    /**
     *
     */
    public function test_count()
    {
        $this->assertEquals(1, Prime::repository('Bdf\Prime\TestEntity')->count());

        Prime::push('Bdf\Prime\TestEntity', [
            'name' => 'entity 2',
        ]);

        $this->assertEquals(2, Prime::repository('Bdf\Prime\TestEntity')->count());
        $this->assertEquals(1, Prime::repository('Bdf\Prime\TestEntity')->count(['name :like' => '%2']));
    }

    /**
     * 
     */
    public function test_all()
    {
        $expected = $this->getTestPack()->get('entity');
        $entities = Prime::repository('Bdf\Prime\TestEntity')->all();
        
        $this->assertTrue(is_array($entities), 'should be an array');
        $this->assertSameEntity($expected, $entities[0]);
    }
    
    /**
     * 
     */
    public function test_find_entities()
    {
        $repository = Prime::repository(TestEntity::class);
        
        $entity = $repository->findOne(['name :like' => 'ent%']);
        $this->assertInstanceOf(TestEntity::class, $entity);
        
        $entity = $repository->findOne(['name :like' => 'emtpy']);
        $this->assertNull($entity);
    }
    
    /**
     * 
     */
    public function test_find_one_entity()
    {
        $repository = Prime::repository('Bdf\Prime\TestEntity');
        
        $this->assertNotNull($repository->findOne(['id' => 1]));
        $this->assertNull($repository->findOne(['id' => 'empty']));
    }
    
    /**
     * 
     */
    public function test_read_entity_values()
    {
        $expected = $this->getTestPack()->get('entity');
        
        $repository = Prime::repository('Bdf\Prime\TestEntity');
        
        $entity = $repository->findOne(['id' => 1]);
        
        $this->assertSameEntity($expected, $entity);
    }
    
    /**
     * 
     */
    public function test_find_by_id()
    {
        $expected = $this->getTestPack()->get('entity');
        
        $repository = Prime::repository('Bdf\Prime\TestEntity');
        
        $this->assertSameEntity($expected, $repository->get(1));
        $this->assertNull($repository->get('unknow'));
    }
    
    /**
     * 
     */
    public function test_find_with_invalid_id()
    {
        $this->assertNull(Prime::repository('Bdf\Prime\TestEntity')->get(null));
        $this->assertNull(Prime::repository('Bdf\Prime\TestEntity')->get(0));
    }
    
    /**
     * 
     */
    public function test_find_by_id_with_criteria()
    {
        $expected = $this->getTestPack()->get('entity');
        
        $repository = Prime::repository('Bdf\Prime\TestEntity');
        
        $this->assertSameEntity($expected, $repository->get(['id' => 1]));
    }
    
    /**
     * 
     */
    public function test_get_or_fail()
    {
        $expected = $this->getTestPack()->get('entity');
        
        $repository = Prime::repository('Bdf\Prime\TestEntity');
        
        $this->assertSameEntity($expected, $repository->getOrFail(1));
        
        $this->expectException('Bdf\Prime\Exception\EntityNotFoundException');
        $repository->getOrFail('unknow');
    }
    
    /**
     * 
     */
    public function test_get_or_new()
    {
        $expected = $this->getTestPack()->get('entity');
        
        $repository = Prime::repository('Bdf\Prime\TestEntity');
        
        $this->assertSameEntity($expected, $repository->getOrNew(1));
        $this->assertSameEntity(new TestEntity(), $repository->getOrNew('unknow'));
    }

    /**
     * @todo test isNew avec une cle composite
     */
    public function test_isNew()
    {
        $repository = Prime::repository('Bdf\Prime\TestEntity');
        $entity = $repository->entity();

        $this->assertTrue($repository->isNew($entity), 'entity is new');

        $entity->id = 1;
        $this->assertFalse($repository->isNew($entity), 'entity is not new');
    }

    /**
     *
     */
    public function test_exists()
    {
        Prime::push('Bdf\Prime\TestEntity', [
            'id'         => 21,
            'name'       => 'relation',
            'foreign'    => ['id' => 20]
        ]);

        $repository = Prime::repository('Bdf\Prime\TestEntity');

        $entity = $repository->entity([
            'id' => 21,
        ]);

        $this->assertTrue($repository->exists($entity));

        $entity->id = 20;
        $this->assertFalse($repository->exists($entity));
    }

    /**
     *
     */
    public function test_refresh()
    {
        $entity = TestEntity::entity([
            'id'   => 21,
            'name' => 'base'
        ]);
        $entity->save();

        Prime::push('Bdf\Prime\TestEntity', [
            'id'   => 21,
            'name' => 'rebase'
        ]);

        $this->assertEquals('base', $entity->name);
        $this->assertEquals('rebase', TestEntity::refresh($entity)->name);
        $this->assertNull(TestEntity::refresh($entity, ['name' => 'base']));
        $this->assertNotNull(TestEntity::refresh($entity, ['name' => 'rebase']));
    }

    /**
     *
     */
    public function test_update_event_can_update_attribute()
    {
        TestEntity::updating(function($entity, $repository, $attributes)  {
            $attributes[] = 'name';
        });

        $entity = TestEntity::entity(['id' => 21, 'name' => 'test']);
        $entity->insert();
        $entity->name = 'tested';
        $entity->update(['id']);

        $entity = TestEntity::refresh($entity);
        $this->assertEquals('tested', $entity->name);
    }

    /**
     * 
     */
    public function test_load_event()
    {
        $eventTrigered = false;
        
        $repository = Prime::repository('Bdf\Prime\TestEntity');
        
        $repository->once(Events::POST_LOAD, function($entity) use(&$eventTrigered) {
            $eventTrigered = true;
            $this->assertEquals(1, $entity->id);
        });
        
        $this->assertTrue($repository->hasListeners(Events::POST_LOAD), 'has load listener');
        
        $repository->findOne([
            'id' => 1,
        ]);
        
        $this->assertTrue($eventTrigered, 'event has trigered');
    }

    /**
     * 
     */
    public function test_load_relation_on_existing_entity()
    {
        Prime::push('Bdf\Prime\TestEmbeddedEntity', [
            'id'   => 20,
            'name' => 'test embedded'
        ]);
        Prime::push('Bdf\Prime\TestEntity', [
            'id'         => 21,
            'name'       => 'relation',
            'foreign'    => ['id' => 20]
        ]);
        
        $repository = Prime::repository('Bdf\Prime\TestEntity');
        
        $entity = $repository->findOne([
            'id' => 21,
        ]);
        
        $this->assertEquals(20, $entity->foreign->id);
        $this->assertNull($entity->foreign->name);
        
        $repository->loadRelations($entity, 'foreign');
        
        $this->assertEquals(20, $entity->foreign->id);
        $this->assertEquals('test embedded', $entity->foreign->name);
    }

    /**
     *
     */
    public function test_reloadRelations()
    {
        Prime::push('Bdf\Prime\TestEmbeddedEntity', [
            'id'   => 20,
            'name' => 'test embedded'
        ]);
        Prime::push('Bdf\Prime\TestEntity', [
            'id'         => 21,
            'name'       => 'relation',
            'foreign'    => ['id' => 20]
        ]);

        $repository = Prime::repository('Bdf\Prime\TestEntity');

        $entity = $repository->findOne([
            'id' => 21,
        ]);

        $this->assertNull($entity->foreign->name);

        $repository->loadRelations($entity, 'foreign');
        $loadedForeign = $entity->foreign;

        $repository->loadRelations($entity, 'foreign');
        $this->assertSame($loadedForeign, $entity->foreign);

        $repository->reloadRelations($entity, 'foreign');
        $this->assertNotSame($loadedForeign, $entity->foreign);
    }
    
    /**
     * 
     */
    public function test_load_relation()
    {
        Prime::push('Bdf\Prime\TestEmbeddedEntity', [
            'id'   => 20,
            'name' => 'test embedded'
        ]);
        Prime::push('Bdf\Prime\TestEntity', [
            'id'         => 21,
            'name'       => 'relation',
            'foreign'    => ['id' => 20]
        ]);
        
        $repository = Prime::repository('Bdf\Prime\TestEntity');
        
        $entity = $repository->with('foreign')->findOne([
            'id' => 21,
        ]);
        
        $this->assertEquals(20, $entity->foreign->id);
        $this->assertEquals('test embedded', $entity->foreign->name);
    }

    /**
     *
     */
    public function test_on_relation()
    {
        Prime::push('Bdf\Prime\TestEmbeddedEntity', [
            'id'   => 20,
            'name' => 'test embedded'
        ]);
        Prime::push('Bdf\Prime\TestEntity', [
            'id'         => 21,
            'name'       => 'relation',
            'foreign'    => ['id' => 20]
        ]);

        $repository = Prime::repository('Bdf\Prime\TestEntity');

        $entity = $repository->get(21);
        $foreign = $repository->onRelation('foreign', $entity)->first();

        $this->assertEquals(20, $foreign->id);
        $this->assertEquals('test embedded', $foreign->name);
    }

    /**
     * 
     */
    public function test_on_connection()
    {
        Prime::service()->connections()->declareConnection('slave', [
            'adapter' => 'sqlite',
            'memory'  => true
        ]);
        
        $expectedOnDefault = $this->getTestPack()->get('entity');
        $expectedOnSlave = clone $this->getTestPack()->get('entity');
        $expectedOnSlave->id = 10;
        
        $repository = Prime::repository('Bdf\Prime\TestEntity');
        $result = $repository->on('slave', function($repository) use($expectedOnSlave) {
            Prime::create('Bdf\Prime\TestEntity', true);
            Prime::push($expectedOnSlave);
            
            $result = $repository->get(10);
            
            Prime::drop('Bdf\Prime\TestEntity', true);
            
            return $result;
        });

        Prime::service()->connections()->removeConnection('slave');

        $this->assertSameEntity($expectedOnSlave, $result);
        $this->assertSameEntity($expectedOnDefault, $repository->get(1), 'connection should be the default one');
    }
    
    /**
     * 
     */
    public function test_on_connection_release_connection_on_failure()
    {
        Prime::service()->connections()->declareConnection('slave', [
            'adapter' => 'sqlite',
            'memory'  => true
        ]);
    
        $failureOk = false;
        $expectedOnDefault = $this->getTestPack()->get('entity');
        
        $repository = Prime::repository('Bdf\Prime\TestEntity');
        
        try {
            $repository->on('slave', function($repository) {
                return $repository->get(10);
            });
        } catch (\Exception $e) {
            $failureOk = true;
        }

        Prime::service()->connections()->removeConnection('slave');

        $this->assertTrue($failureOk);
        $this->assertSameEntity($expectedOnDefault, $repository->get(1), 'connection should be the default one');
    }

    /**
     *
     */
    public function test_on_connection_should_reset_old_queries()
    {
        Prime::service()->connections()->declareConnection('slave', [
            'adapter' => 'sqlite',
            'memory'  => true
        ]);

        $expectedOnDefault = $this->getTestPack()->get('entity');
        $expectedOnSlave = clone $expectedOnDefault;
        $expectedOnSlave->name = 'slave entity';

        $repository = TestEntity::repository();
        $this->assertEquals($expectedOnDefault, $repository->findById(1));

        $repository->on('slave');

        Prime::create('Bdf\Prime\TestEntity', true);
        Prime::push($expectedOnSlave);

        $this->assertEquals($expectedOnSlave, $repository->findById(1));

        $repository->on('test');
        $this->assertEquals($expectedOnDefault, $repository->findById(1));

        Prime::service()->connections()->removeConnection('slave');
    }

    /**
     *
     */
    public function test_on_connection_with_closure_should_reset_old_queries()
    {
        Prime::service()->connections()->declareConnection('slave', [
            'adapter' => 'sqlite',
            'memory'  => true
        ]);

        $expectedOnDefault = $this->getTestPack()->get('entity');
        $expectedOnSlave = clone $expectedOnDefault;
        $expectedOnSlave->name = 'slave entity';

        $repository = TestEntity::repository();
        $this->assertEquals($expectedOnDefault, $repository->findById(1));

        $repository->on('slave', function (EntityRepository $repository) use($expectedOnSlave) {
            Prime::create('Bdf\Prime\TestEntity', true);
            Prime::push($expectedOnSlave);

            $this->assertEquals($expectedOnSlave, $repository->findById(1));
        });

        $this->assertEquals($expectedOnDefault, $repository->findById(1));

        Prime::service()->connections()->removeConnection('slave');
    }

    /**
     *
     */
    public function test_on_connection_should_listen_connection_closed()
    {
        Prime::service()->connections()->declareConnection('slave', [
            'adapter' => 'sqlite',
            'memory'  => true
        ]);

        $expectedOnDefault = $this->getTestPack()->get('entity');
        $expectedOnSlave = clone $expectedOnDefault;
        $expectedOnSlave->name = 'slave entity';

        $repository = TestEntity::repository();
        $this->assertEquals($expectedOnDefault, $repository->findById(1));

        $repository->on('slave');

        Prime::create('Bdf\Prime\TestEntity', true);
        Prime::push($expectedOnSlave);

        $this->assertEquals($expectedOnSlave, $repository->findById(1));
        Prime::service()->connection('slave')->close();

        Prime::create('Bdf\Prime\TestEntity');
        $this->assertNull($repository->findById(1));

        $repository->on('test');
        Prime::service()->connections()->removeConnection('slave');
    }

    /**
     *
     */
    public function test_saveAll_save_entity_and_relations()
    {
        $entity = $this->getTestPack()->get('entity');
        $entity->name = 'saveAll';
        $entity->foreign->name = 'saveAll foreign';

        $nb = Prime::repository('Bdf\Prime\TestEntity')->saveAll($entity, 'foreign');

        $entity = Prime::repository('Bdf\Prime\TestEntity')
            ->with('foreign')
            ->get($entity->id);

        $this->assertEquals(2, $nb);
        $this->assertEquals('saveAll', $entity->name);
        $this->assertEquals('saveAll foreign', $entity->foreign->name);
    }

    /**
     *
     */
    public function test_deleteAll_delete_entity_and_relations()
    {
        $entity = $this->getTestPack()->get('entity');
        $foreign = $entity->foreign;

        $nb = Prime::repository('Bdf\Prime\TestEntity')->deleteAll($entity, 'foreign');

        $foreign = Prime::refresh($foreign);
        $entity = Prime::refresh($entity);

        $this->assertEquals(2, $nb);
        $this->assertEquals(null, $entity);
        $this->assertEquals(null, $foreign);
    }

    /**
     *
     */
    public function test_collection()
    {
        $repository = Prime::repository('Bdf\Prime\TestEntity');

        $entity = new TestEntity();

        $collection = $repository->collection([$entity]);

        $this->assertSame($repository, $collection->repository());
        $this->assertSame([$entity], $collection->all());

        $this->assertEquals($collection, $repository->collection([$entity]));
        $this->assertNotSame($collection, $repository->collection([$entity]));
    }

    /**
     *
     */
    public function test_builder_quote_identifier()
    {
        $repository = Prime::repository(Right::class);

        $query = $repository->builder();

        $this->assertTrue($query->isQuoteIdentifier());
    }

    /**
     *
     */
    public function test_close_connection_should_reset_queries()
    {
        $respository = TestEntity::repository();
        $this->assertTrue($this->prime()->connection('test')->schema()->hasTable('test_'));

        $this->assertEquals($this->getTestPack()->get('entity'), $respository->findById(1));


        // Close connection : will destroy all data
        $this->prime()->connection('test')->close();
        $this->assertFalse($this->prime()->connection('test')->schema()->hasTable('test_'));

        // Recreate the schema
        TestEntity::repository()->schema()->migrate();

        // Data is removed
        $this->assertNull($respository->findById(1));
    }
}
