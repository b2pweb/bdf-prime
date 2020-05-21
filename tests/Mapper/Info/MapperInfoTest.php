<?php

namespace Bdf\Prime\Mapper\Info;

use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class MapperInfoTest extends TestCase
{
    use PrimeTestCase;
    
    protected $info;
    
    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();
        
        $this->info = new MapperInfo(Prime::repository('Bdf\Prime\User')->mapper());
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
    public function test_accessor()
    {
        $repository = Prime::repository('Bdf\Prime\User');
        
        $this->assertEquals($repository->mapper(), $this->info->mapper());
        $this->assertEquals($repository->metadata(), $this->info->metadata());
        $this->assertEquals($repository->metadata()->connection, $this->info->connection());
    }
    
    /**
     * 
     */
    public function test_classname()
    {
        $this->assertEquals('Bdf\Prime\User', $this->info->className());
    }
    
    /**
     * 
     */
    public function test_properties()
    {
        $expected = [
            'id',
            'name',
            'roles',
        ];
        $i = 0;
        
        foreach ($this->info->properties() as $property) {
            $this->assertEquals($expected[$i++], $property->name());
        }
    }

    /**
     *
     */
    public function test_primaries()
    {
        $expected = [
            'id',
        ];
        $i = 0;

        foreach ($this->info->primaries() as $property) {
            $this->assertEquals($expected[$i++], $property->name());
        }
    }

    /**
     * 
     */
    public function test_embedded()
    {
        $expected = [
            'customer.id',
            'faction.id',
        ];
        $i = 0;
        
        foreach ($this->info->embedded() as $property) {
            $this->assertEquals($expected[$i++], $property->name());
        }
    }
    
    /**
     * 
     */
    public function test_objects()
    {
        $expected = [
            'customer',
            'faction',
            'documents',
        ];
        $i = 0;
        
        foreach ($this->info->objects() as $property) {
            $this->assertEquals($expected[$i++], $property->name());
        }
    }
    
    /**
     * 
     */
    public function test_property()
    {
        $property = $this->info->property('customer');
        $this->assertInstanceOf('Bdf\Prime\Mapper\Info\ObjectPropertyInfo', $property);
        
        $property = $this->info->property('null');
        $this->assertEquals(null, $property);
    }
    
    /**
     * 
     */
    public function test_all()
    {
        $expected = [
            'id',
            'name',
            'roles',
            'customer',
            'faction',
            'documents',
        ];
        $i = 0;
        
        foreach ($this->info->properties() as $property) {
            $this->assertEquals($expected[$i++], $property->name());
        }
    }
}
