<?php

namespace Bdf\Prime\Schema;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class NullResolverTest extends TestCase
{
    /**
     * 
     */
    public function test_interface()
    {
        $schema = new NullStructureUpgrader();

        $schema->migrate();
        $this->assertEquals([], $schema->diff());
        $this->assertTrue($schema->truncate());
        $this->assertTrue($schema->drop());
    }
}