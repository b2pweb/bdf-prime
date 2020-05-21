<?php

namespace Bdf\Prime;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class PrimeTest extends TestCase
{
    use PrimeTestCase;

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
        $pack->declareEntity([
            TestEntity::class,
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
    public function test_configure()
    {
        $save = Prime::service();
        
        Prime::configure($service = new ServiceLocator());
        $this->assertSame($service, Prime::service());
        
        Prime::configure($save);
    }
    
    /**
     * 
     */
    public function test_service()
    {
        $this->assertInstanceOf('Bdf\Prime\ServiceLocator', Prime::service());
    }
    
    /**
     * 
     */
    public function test_connection()
    {
        $this->assertInstanceOf('Doctrine\DBAL\Connection', Prime::connection('test'));
    }
    
    /**
     * 
     */
    public function test_repository()
    {
        $this->assertInstanceOf('Bdf\Prime\Repository\EntityRepository', Prime::repository('Bdf\Prime\TestEntity'));
    }
    
    /**
     * 
     */
    public function test_truncate()
    {
        $repository = Prime::repository('Bdf\Prime\TestEntity');
        
        Prime::push($repository, [
            'name' => __FUNCTION__
        ]);
        
        $this->assertEquals(1, $repository->count(), 'before truncate');
        Prime::truncate($repository);
        $this->assertEquals(0, $repository->count(), 'after truncate');
    }
    
    /**
     * 
     */
    public function test_push()
    {
        $repository = Prime::repository('Bdf\Prime\TestEntity');
        
        Prime::push($repository, [
            'name' => 1
        ]);
        $this->assertEquals(1, $repository->count(), "Prime::push(repository, ['id' => '...']);");
        
        Prime::push($repository->entity([
            'name' => 2
        ]));
        $this->assertEquals(2, $repository->count(), "Prime::push(new EntityClass());");
        
        Prime::push([
            $repository->entity(['name' => 3]),
            $repository->entity(['name' => 3]),
        ]);
        $this->assertEquals(4, $repository->count(), "Prime::push([new EntityClass()]);");
        
        Prime::push('Bdf\Prime\TestEntity', [
            'name' => 4
        ]);
        $this->assertEquals(5, $repository->count(), "Prime::push('name', ['id' => '...']);");
        
        Prime::push('Bdf\Prime\TestEntity', [
            ['name' => 5],
            ['name' => 5],
        ]);
        $this->assertEquals(7, $repository->count(), "Prime::push('name', [['id' => '...']]);");
    }
    
    /**
     * 
     */
    public function test_save()
    {
        $repository = Prime::repository('Bdf\Prime\TestEntity');
        
        Prime::save($repository, [
            'name' => 1
        ]);
        $this->assertEquals(1, $repository->count(), "Prime::save(repository, ['id' => '...']);");
        
        Prime::save($repository->entity([
            'name' => 2
        ]));
        $this->assertEquals(2, $repository->count(), "Prime::save(new EntityClass());");
        
        Prime::save([
            $repository->entity(['name' => 3]),
            $repository->entity(['name' => 3]),
        ]);
        $this->assertEquals(4, $repository->count(), "Prime::save([new EntityClass()]);");
        
        Prime::save('Bdf\Prime\TestEntity', [
            'name' => 4
        ]);
        $this->assertEquals(5, $repository->count(), "Prime::save('name', ['id' => '...']);");
        
        Prime::save('Bdf\Prime\TestEntity', [
            ['name' => 5],
            ['name' => 5],
        ]);
        $this->assertEquals(7, $repository->count(), "Prime::save('name', [['id' => '...']]);");
    }
    
    /**
     * 
     */
    public function test_remove()
    {
        $repository = Prime::repository('Bdf\Prime\TestEntity');

        Prime::save('Bdf\Prime\TestEntity', [
            ['name' => 1],
            ['name' => 2],
            ['name' => 3],
            ['name' => 4],
            ['name' => 5],
            ['name' => 6],
            ['name' => 7],
        ]);

        Prime::remove($repository, [
            'id' => 1
        ]);
        $this->assertEquals(6, $repository->count(), "Prime::push(repository, ['id' => '...']);");
        
        Prime::remove($repository->entity([
            'id' => 2
        ]));
        $this->assertEquals(5, $repository->count(), "Prime::push(new EntityClass());");
        
        Prime::remove([
            $repository->entity(['id' => 3]),
            $repository->entity(['id' => 4]),
        ]);
        $this->assertEquals(3, $repository->count(), "Prime::push([new EntityClass()]);");
        
        Prime::remove('Bdf\Prime\TestEntity', [
            'id' => 5
        ]);
        $this->assertEquals(2, $repository->count(), "Prime::push('name', ['id' => '...']);");
        
        Prime::remove('Bdf\Prime\TestEntity', [
            ['id' => 6],
            ['id' => 7],
        ]);
        $this->assertEquals(0, $repository->count(), "Prime::push('name', [['id' => '...']]);");
    }
    
    /**
     * 
     */
    public function test_exists()
    {
        $repository = Prime::repository('Bdf\Prime\TestEntity');
        
        $entity = $repository->entity(['name' => __FUNCTION__]);
        Prime::push($entity);
        
        $this->assertTrue(Prime::exists($entity));
        
        $entity->name = 'other name';
        $this->assertFalse(Prime::exists($entity));
        $this->assertTrue(Prime::exists($entity, false));
    }
    
    /**
     * 
     */
    public function test_find()
    {
        $repository = Prime::repository('Bdf\Prime\TestEntity');
        
        $entity = $repository->entity(['name' => __FUNCTION__]);
        Prime::push($entity);
        
        $this->assertEquals([$entity], Prime::find($repository, ['name' => __FUNCTION__]), "Prime::find(repository, ['id' => '...']);");
        $this->assertEquals([$entity], Prime::find('Bdf\Prime\TestEntity', ['name' => __FUNCTION__]), "Prime::find('name', ['id' => '...']);");
    }
    
    /**
     * 
     */
    public function test_one()
    {
        $repository = Prime::repository('Bdf\Prime\TestEntity');
        
        $entity = $repository->entity(['name' => __FUNCTION__]);
        Prime::push($entity);
        
        $this->assertEquals($entity, Prime::one($repository, ['name' => __FUNCTION__]), 'Prime::one');
        $this->assertEquals($entity, Prime::one($entity), 'Prime::one(new EntityClass())');
    }
}
