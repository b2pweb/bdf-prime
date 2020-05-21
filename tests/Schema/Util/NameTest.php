<?php

namespace Bdf\Prime\Schema\Util;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class NameTest extends TestCase
{
    /**
     *
     */
    public function test_generate_prefix()
    {
        $this->assertStringStartsWith('PFX_', Name::generate('pfx', ['part']));
    }

    /**
     *
     */
    public function test_generate_length()
    {
        $this->assertEquals(32, strlen(Name::generate('pfx', range(1000, 1500), 32)));
    }

    /**
     *
     */
    public function test_generate_only_hexa()
    {
        $this->assertTrue(ctype_xdigit(substr(Name::generate('', ['foo', 'bar']), 1)));
    }

    /**
     *
     */
    public function test_serialized_prefix()
    {
        $this->assertStringStartsWith('PFX_', Name::serialized('pfx', ['part']));
    }

    /**
     *
     */
    public function test_serialized_length()
    {
        $this->assertEquals(32, strlen(Name::serialized('pfx', range(1000, 1500), 32)));
    }

    /**
     *
     */
    public function test_serialized_only_hexa()
    {
        $this->assertTrue(ctype_xdigit(substr(Name::serialized('', ['foo', 'bar']), 1)));
    }
}
