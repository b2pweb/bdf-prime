<?php

namespace Php81\Query\Criteria;

use Bdf\Prime\Query\Criteria\LikeCriterion;
use Bdf\Prime\Query\Expression\Like;
use PHPUnit\Framework\TestCase;

class LikeCriterionTest extends TestCase
{
    public function test_default()
    {
        $criterion = new LikeCriterion();

        $this->assertSame('foo', $criterion->field('foo'));
        $this->assertEquals((new Like('bar'))->escape(), $criterion->value('bar'));
    }

    public function test_field()
    {
        $criterion = new LikeCriterion(field: 'other');

        $this->assertSame('other', $criterion->field('foo'));
        $this->assertEquals((new Like('bar'))->escape(), $criterion->value('bar'));
    }

    public function test_startsWith()
    {
        $criterion = new LikeCriterion(startsWith: true);

        $this->assertSame('foo', $criterion->field('foo'));
        $this->assertEquals((new Like('bar'))->escape()->startsWith(), $criterion->value('bar'));
    }

    public function test_endsWith()
    {
        $criterion = new LikeCriterion(endsWith: true);

        $this->assertSame('foo', $criterion->field('foo'));
        $this->assertEquals((new Like('bar'))->escape()->endsWith(), $criterion->value('bar'));
    }

    public function test_contains()
    {
        $criterion = new LikeCriterion(contains: true);

        $this->assertSame('foo', $criterion->field('foo'));
        $this->assertEquals((new Like('bar'))->escape()->contains(), $criterion->value('bar'));
    }

    public function test_escape()
    {
        $criterion = new LikeCriterion(escape: false);

        $this->assertSame('foo', $criterion->field('foo'));
        $this->assertEquals((new Like('bar')), $criterion->value('bar'));
    }

    public function test_start()
    {
        $criterion = new LikeCriterion(start: '%;');

        $this->assertSame('foo', $criterion->field('foo'));
        $this->assertEquals((new Like('bar'))->escape()->start('%;'), $criterion->value('bar'));
    }

    public function test_end()
    {
        $criterion = new LikeCriterion(end: '%;');

        $this->assertSame('foo', $criterion->field('foo'));
        $this->assertEquals((new Like('bar'))->escape()->end('%;'), $criterion->value('bar'));
    }
}
