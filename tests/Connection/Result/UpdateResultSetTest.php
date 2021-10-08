<?php

namespace Connection\Result;

use Bdf\Prime\Connection\Result\UpdateResultSet;
use PHPUnit\Framework\TestCase;

class UpdateResultSetTest extends TestCase
{
    /**
     *
     */
    public function test_fetchModes_should_do_nothing()
    {
       $rs = new UpdateResultSet(2);
       $original = clone $rs;

       $this->assertSame($rs, $rs->asAssociative());
       $this->assertSame($rs, $rs->asList());
       $this->assertSame($rs, $rs->asObject());
       $this->assertSame($rs, $rs->asColumn());
       $this->assertSame($rs, $rs->asClass('Foo'));
       $this->assertSame($rs, $rs->fetchMode('foo'));

       $this->assertEquals($original, $rs);
    }

    /**
     *
     */
    public function test_getters()
    {
        $rs = new UpdateResultSet(2);

        $this->assertEquals(2, $rs->count());
        $this->assertCount(2, $rs);
        $this->assertFalse($rs->isRead());
        $this->assertTrue($rs->isWrite());
        $this->assertTrue($rs->hasWrite());
        $this->assertSame([], $rs->all());
        $this->assertSame([], iterator_to_array($rs));

        $this->assertFalse((new UpdateResultSet(0))->hasWrite());
    }
}
