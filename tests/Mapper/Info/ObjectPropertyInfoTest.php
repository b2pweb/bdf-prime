<?php

namespace Bdf\Prime\Mapper\Info;

use Bdf\Prime\Customer;
use Bdf\Prime\Folder;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ObjectPropertyInfoTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var MapperInfo
     */
    protected $info;
    
    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();
        
        $this->info = new MapperInfo(Prime::repository(User::class)->mapper());
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
    public function test_name()
    {
        $this->assertEquals('customer', $this->info->property('customer')->name());
    }
    
    /**
     * 
     */
    public function test_is_object()
    {
        $this->assertTrue($this->info->property('customer')->isObject());
    }

    /**
     *
     */
    public function test_is_embedded()
    {
        $this->assertTrue($this->info->property('customer')->isEmbedded());
        $this->assertFalse($this->info->property('name')->isEmbedded());

        $info = new MapperInfo(Prime::repository(Customer::class)->mapper());
        $this->assertFalse($info->relations()['users']->isEmbedded());
    }

    /**
     *
     */
    public function test_belongs_to_root()
    {
        $this->assertTrue($this->info->property('customer')->belongsToRoot());
        $this->assertFalse($this->info->property('customer.id')->belongsToRoot());
        $this->assertTrue($this->info->property('name')->belongsToRoot());
        $this->assertTrue($this->info->property('documents')->belongsToRoot());

        $info = new MapperInfo(Prime::repository(Customer::class)->mapper());
        $this->assertFalse($info->relations()['users']->belongsToRoot());
    }

    /**
     * 
     */
    public function test_is_relation()
    {
        $this->assertTrue($this->info->property('customer')->isRelation());
    }
    
    /**
     * 
     */
    public function test_is_array()
    {
        $this->assertFalse($this->info->property('customer')->isArray());
        $this->assertTrue($this->info->property('documents')->isArray());
    }
    
    /**
     * 
     */
    public function test_classname()
    {
        $this->assertEquals(Customer::class, $this->info->property('customer')->className());
    }

    /**
     *
     */
    public function test_wrapper()
    {
        $this->assertNull($this->info->property('documents')->wrapper());

        $info = new MapperInfo(Folder::repository()->mapper());
        $this->assertEquals('collection', $info->property('files')->wrapper());
    }
}
