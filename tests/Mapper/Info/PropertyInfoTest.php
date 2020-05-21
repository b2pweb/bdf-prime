<?php

namespace Bdf\Prime\Mapper\Info;

use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Task;
use Bdf\Prime\TestEntity;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class PropertyInfoTest extends TestCase
{
    use PrimeTestCase;

    /** @var MapperInfo */
    protected $info;
    
    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();
        
        $this->info = User::repository()->mapper()->info();
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
        $this->assertEquals('id', $this->info->property('id')->name());
    }
    
    /**
     * 
     */
    public function test_type()
    {
        $this->assertEquals('bigint', $this->info->property('id')->type());
    }

    /**
     *
     */
    public function test_alias()
    {
        $this->assertEquals('id_', $this->info->property('id')->alias());
    }

    /**
     * 
     */
    public function test_unknown_phpType()
    {
        $info = new PropertyInfo('foo', ['type' => 'bar']);

        $this->assertEquals('bar', $info->phpType());
    }

    /**
     *
     */
    public function test_known_phpType()
    {
        $this->assertEquals('string', $this->info->property('id')->phpType());
    }

    /**
     * 
     */
    public function test_is_primary()
    {
        $this->assertTrue($this->info->property('id')->isPrimary());
        $this->assertFalse($this->info->property('name')->isPrimary());
    }

    /**
     *
     */
    public function test_is_embedded()
    {
        $this->assertTrue($this->info->property('customer.id')->isEmbedded());
        $this->assertFalse($this->info->property('name')->isEmbedded());
    }

    /**
     *
     */
    public function test_is_object()
    {
        $this->assertTrue($this->info->property('customer')->isObject());
        $this->assertFalse($this->info->property('id')->isObject());
    }

    /**
     * 
     */
    public function test_is_array()
    {
        $this->assertTrue($this->info->property('roles')->isArray());
        $this->assertFalse($this->info->property('id')->isArray());
    }
    
    /**
     * 
     */
    public function test_has_default()
    {
        $this->assertFalse($this->info->property('id')->hasDefault());
    }
    
    /**
     * 
     */
    public function test_get_default()
    {
        $this->assertEquals(null, $this->info->property('id')->getDefault());
    }
    
    /**
     * 
     */
    public function test_convert_toPhp()
    {
        $this->assertEquals('12', $this->info->property('id')->convert(12));
    }

    /**
     *
     */
    public function test_convert_toDatabase()
    {
        $this->assertEquals(',1,2,3,', $this->info->property('roles')->convert([1, 2, 3], false));
    }

    /**
     *
     */
    public function test_is_datetime()
    {
        $info = TestEntity::repository()->mapper()->info();

        $this->assertTrue($info->property('dateInsert')->isDateTime());
        $this->assertNull($info->property('dateInsert')->getTimezone());
        $this->assertFalse($info->property('id')->isDateTime());
        $this->assertNull($info->property('id')->getTimezone());

        // Immutable
        $info = Task::repository()->mapper()->info();
        $this->assertTrue($info->property('createdAt')->isDateTime());
        $this->assertTrue($info->property('updatedAt')->isDateTime());
        $this->assertTrue($info->property('deletedAt')->isDateTime());
        $this->assertSame('UTC', $info->property('createdAt')->getTimezone());
        $this->assertSame('UTC', $info->property('updatedAt')->getTimezone());
        $this->assertNotEquals('UTC', $info->property('deletedAt')->getTimezone());
        $this->assertSame('\DateTimeImmutable', $info->property('createdAt')->phpType());
        $this->assertSame('\DateTimeImmutable', $info->property('updatedAt')->phpType());
        $this->assertSame('\DateTime', $info->property('deletedAt')->phpType());
    }
}
