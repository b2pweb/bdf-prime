<?php

namespace Php81\Query\Criteria;

use Bdf\Prime\Query\Criteria\CustomCriteria;
use Bdf\Prime\Query\Criteria\FilterEntry;
use Bdf\Prime\Query\Expression\Attribute;
use Bdf\Prime\Query\Expression\Json\JsonExtract;
use Php81\Query\Criteria\Fixtures\NullableCriteria;
use Php81\Query\Criteria\Fixtures\SimpleCriteria;
use Php81\Query\Criteria\Fixtures\WithLeftExpressionCriteria;
use PHPUnit\Framework\TestCase;

class CustomCriteriaTest extends TestCase
{
    public function test_default()
    {
        $criteria = new class extends CustomCriteria {};

        $this->assertSame([], iterator_to_array($criteria));
        $this->assertNull($criteria->separator());
    }

    public function test_iterator_simple_criteria()
    {
        $criteria = new SimpleCriteria('foo', 10);
        $filters = iterator_to_array($criteria);

        $this->assertSame([
            'name' => 'foo',
            'value >=' => 10,
        ], $filters);
    }

    public function test_iterator_nullable_criteria()
    {
        $criteria = new NullableCriteria();
        $this->assertSame([
            'bar' => null,
        ], iterator_to_array($criteria));

        $criteria->foo = 'a';
        $this->assertSame([
            'foo' => 'a',
            'bar' => null,
        ], iterator_to_array($criteria));
    }

    public function test_iterator_left_expression()
    {
        $criteria = new WithLeftExpressionCriteria(42, 'hash');
        $this->assertEquals([
            'value' => new FilterEntry(new JsonExtract('metadata', 'tag'), '>=', 42),
            'hash' => new FilterEntry(new Attribute('content', 'MD5(%s)'), '=', 'hash'),
        ], iterator_to_array($criteria));
    }
}
