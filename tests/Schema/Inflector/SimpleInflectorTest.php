<?php

namespace Bdf\Prime\Schema\Inflector;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class SimpleInflectorTest extends TestCase
{
    /**
     * 
     */
    public function test_create_schema()
    {
        $inflector = new SimpleInfector();
        
        $this->assertEquals('FooBar', $inflector->getClassName('foo_bar'));
        $this->assertEquals('fooBar', $inflector->getPropertyName('foo', 'foo_bar'));
        $this->assertEquals('foo_bar_seq', $inflector->getSequenceName('foo_bar'));
    }
}