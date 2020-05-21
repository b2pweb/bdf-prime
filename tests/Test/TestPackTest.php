<?php

namespace Bdf\Prime\Test;

use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\User;
use Bdf\Prime\Customer;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class TestPackTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var TestPack 
     */
    protected $pack;
    
    /**
     * Basic user for tests
     * 
     * @var User 
     */
    protected $basicUser;
    
    /**
     * Basic customer for tests
     * 
     * @var Customer 
     */
    protected $basicCustomer;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->configurePrime();

        $this->pack = new TestPack();
        
        $this->basicUser = new User([
            'id'            => 1,
            'name'          => 'TEST1',
            'customer'      => new Customer(['id' => '1']),
            'roles'         => ['2']
        ]);
        
        $this->basicCustomer = new Customer([
            'name'          => 'TEST',
        ]);
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->pack->destroy();
    }

    /**
     * 
     */
    public function test_initialize_set_initialized_to_true()
    {
        $this->assertFalse($this->pack->isInitialized(), 'Error on isInitialized before');
        
        $this->pack->initialize();
        
        $this->assertTrue($this->pack->isInitialized(), 'Error on isInitialized after');
    }
    
    /**
     * 
     */
    public function test_initialize_create_and_push_entities()
    {
        $this->pack->persist($this->basicUser);
        $this->pack->persist($this->basicCustomer);
        
        $this->pack->initialize();
        
        $this->assertTrue(Prime::exists($this->basicUser, false), 'user exists');
        $this->assertTrue(Prime::exists($this->basicCustomer, false), 'customer exists');
        
        $this->pack->destroy();
        
        $this->expectException('Bdf\Prime\Exception\DBALException');
        $this->assertFalse(Prime::exists($this->basicUser), 'user exists');
    }
    
    /**
     * 
     */
    public function test_reinitialize()
    {
        $this->pack->declareEntity('Bdf\Prime\User');
        $this->pack->persist($this->basicCustomer);
        $this->pack->initialize();
        $this->pack->nonPersist($this->basicUser);

        $user = Prime::one('Bdf\Prime\User', ['id' => 1]);
        $customer = Prime::one('Bdf\Prime\Customer', ['id' => 1]);
        
        $user->name = 'modified';
        $customer->name = 'modified';
        Prime::push($user);
        Prime::push($customer);
        
        $this->pack->clear();

        $this->assertEquals(0, Prime::repository('Bdf\Prime\User')->count(), 'user should be removed');
        $this->assertEquals($this->basicCustomer->name, Prime::repository('Bdf\Prime\Customer')->get(1)->name, 'customer has changed');
    }
    
    /**
     * 
     */
    public function test_persist_no_create_entity_if_initialized()
    {
        $this->pack->persist($this->basicCustomer);
        $this->pack->initialize();
        
        $this->pack->persist(new Customer(['name' => 'TEST2']));
        
        $this->assertEquals(1, Prime::repository('Bdf\Prime\Customer')->count(), 'Error on customer existence');
    }
    
    /**
     * 
     */
    public function test_nonPersist_create_entity_if_initialized()
    {
        $this->pack->initialize();
        
        $this->pack->nonPersist($this->basicCustomer);
        
        $this->assertEquals(1, Prime::repository('Bdf\Prime\Customer')->count(), 'Error on customer existence');
    }
    
    /**
     * 
     */
    public function test_nonPersist_throw_exception_if_not_initialized()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Non persistent data cannot be declared before initialization');

        $this->pack->nonPersist($this->basicCustomer);
    }
    
    /**
     * 
     */
    public function test_declareEntity_create_table_if_initialized()
    {
        $this->pack->initialize();
        
        $this->pack->declareEntity('Bdf\Prime\User');
        
        $this->assertEquals(0, Prime::repository('Bdf\Prime\User')->count());
    }
    
    /**
     * 
     */
    public function test_get_with_alias()
    {
        $this->pack->persist(['user' => $this->basicUser]);
        $this->pack->persist(['customer' => $this->basicCustomer]);
        
        $this->assertEquals($this->basicUser, $this->pack->get('user'));
        $this->assertEquals($this->basicCustomer, $this->pack->get('customer'));
    }
    
    /**
     * 
     */
    public function test_clear()
    {
        $this->pack->declareEntity([
            'Bdf\Prime\User',
            'Bdf\Prime\Customer'
        ]);
        $this->pack->initialize();
        $this->pack->nonPersist(['user' => $this->basicUser]);
        $this->pack->clear();
        
        $this->assertNull($this->pack->get('user'));
        $this->assertEquals(0, Prime::repository('Bdf\Prime\User')->count(), 'Error on user count');
    }
    
    /**
     * 
     */
    public function test_clear_rollback_to_savePoint()
    {
        $this->pack->declareEntity([
            'Bdf\Prime\User',
            'Bdf\Prime\Customer'
        ]);
        
        $this->pack->persist(['user' => $this->basicUser]);
        
        $this->pack->initialize();
        
        $this->pack->nonPersist(['customer' => $this->basicCustomer]);
        Prime::push(new Customer(['name' => 'created after']));
        
        $this->assertEquals(1, Prime::repository('Bdf\Prime\User')->count(), 'Error on user count before clear');
        $this->assertEquals(2, Prime::repository('Bdf\Prime\Customer')->count(), 'Error on customer count before clear');
        
        $this->pack->clear();
        
        $this->assertEquals(1, Prime::repository('Bdf\Prime\User')->count(), 'Error on user count after clear');
        $this->assertEquals(0, Prime::repository('Bdf\Prime\Customer')->count(), 'Error on customer count after clear');
    }
}