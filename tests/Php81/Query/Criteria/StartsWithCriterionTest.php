<?php

namespace Php81\Query\Criteria;

use Bdf\Prime\Query\Criteria\LikeCriterion;
use Bdf\Prime\Query\Criteria\StartsWithCriterion;
use Bdf\Prime\Query\Expression\Like;
use PHPUnit\Framework\TestCase;

class StartsWithCriterionTest extends TestCase
{
    public function test_default()
    {
        $criterion = new StartsWithCriterion();

        $this->assertSame('foo', $criterion->field('foo'));
        $this->assertEquals((new Like('bar'))->startsWith()->escape(), $criterion->value('bar'));
    }

    public function test_field()
    {
        $criterion = new StartsWithCriterion(field: 'other');

        $this->assertSame('other', $criterion->field('foo'));
        $this->assertEquals((new Like('bar'))->startsWith()->escape(), $criterion->value('bar'));
    }

    public function test_escape()
    {
        $criterion = new StartsWithCriterion(escape: false);

        $this->assertSame('foo', $criterion->field('foo'));
        $this->assertEquals((new Like('bar'))->startsWith(), $criterion->value('bar'));
    }
}
