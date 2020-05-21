<?php

namespace Bdf\Prime\Relations\Util;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class EntityKeysTest extends TestCase
{
    /**
     *
     */
    public function test_hash_equals_keys()
    {
        $keys1 = new EntityKeys(['k1', 'k2']);
        $keys2 = new EntityKeys(['k1', 'k2']);

        $this->assertEquals($keys1->hash(), $keys2->hash());
    }

    /**
     *
     */
    public function test_hash()
    {
        $keys = new EntityKeys(['k1', 'k2']);

        $this->assertEquals($keys->hash(), $keys->hash());
        $this->assertEquals($keys->hash(), (new EntityKeys(['k1', 'k2']))->hash());
        $this->assertNotEquals($keys->hash(), (new EntityKeys(['k3', 'k2']))->hash());
        $this->assertNotEquals($keys->hash(), (new EntityKeys(['k2', 'k1']))->hash());
    }

    /**
     *
     */
    public function test_equals()
    {
        $keys = new EntityKeys(['k1', 'k2']);

        $this->assertTrue($keys->equals($keys));
        $this->assertTrue($keys->equals(new EntityKeys(['k1', 'k2'])));
        $this->assertFalse($keys->equals(new EntityKeys(['k3', 'k2'])));
        $this->assertFalse($keys->equals(new EntityKeys(['k2', 'k1'])));
    }

    /**
     *
     */
    public function test_attach_get()
    {
        $keys = new EntityKeys(['k1', 'k2']);
        $o = new \stdClass();

        $keys->attach($o);
        $this->assertSame($o, $keys->get());
    }

    /**
     *
     */
    public function test_toArray()
    {
        $keys = new EntityKeys(['k1', 'k2']);

        $this->assertSame(['k1', 'k2'], $keys->toArray());
    }
}
