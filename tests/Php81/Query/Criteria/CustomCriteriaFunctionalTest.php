<?php

namespace Php81\Query\Criteria;

use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Query;
use DateTime;
use Php81\Query\Criteria\Fixtures\NestedCriteria;
use Php81\Query\Criteria\Fixtures\NullableCriteria;
use Php81\Query\Criteria\Fixtures\SimpleCriteria;
use Php81\Query\Criteria\Fixtures\SimpleOrCriteria;
use Php81\Query\Criteria\Fixtures\WithLeftExpressionCriteria;
use Php81\Query\Criteria\Fixtures\WithLikeCriteria;
use PHPUnit\Framework\TestCase;

class CustomCriteriaFunctionalTest extends TestCase
{
    use PrimeTestCase;

    protected function setUp(): void
    {
        $this->primeStart();;
    }

    protected function tearDown(): void
    {
        $this->primeStop();
    }

    public function test_simple_criteria()
    {
        $query = $this->query()->where(new SimpleCriteria('foo', 42));

        $this->assertSame('SELECT * FROM test WHERE name = ? AND value >= ?', $query->toSql());
        $this->assertSame(['foo', 42], $query->getBindings());
    }

    public function test_or_criteria()
    {
        $query = $this->query()->where(new SimpleOrCriteria('foo', 42));

        $this->assertSame('SELECT * FROM test WHERE name = ? OR value >= ?', $query->toSql());
        $this->assertSame(['foo', 42], $query->getBindings());
    }

    public function test_like_criteria()
    {
        $query = $this->query()->where(new WithLikeCriteria('foo', 'bar.com'));

        $this->assertSame('SELECT * FROM test WHERE name LIKE ? AND email LIKE ?', $query->toSql());
        $this->assertSame(['foo%', '%@bar.com'], $query->getBindings());
    }

    public function test_nullable()
    {
        $criteria = new NullableCriteria();

        $query = $this->query()->where($criteria);
        $this->assertSame('SELECT * FROM test WHERE bar IS NULL', $query->toSql());
        $this->assertSame([], $query->getBindings());

        $criteria->foo = 'foo';
        $query = $this->query()->where($criteria);
        $this->assertSame('SELECT * FROM test WHERE foo = ? AND bar IS NULL', $query->toSql());
        $this->assertSame(['foo'], $query->getBindings());

        $criteria->foo = 'foo';
        $criteria->bar = 'bar';
        $query = $this->query()->where($criteria);
        $this->assertSame('SELECT * FROM test WHERE foo = ? AND bar = ?', $query->toSql());
        $this->assertSame(['foo', 'bar'], $query->getBindings());
    }

    public function test_left_expression()
    {
        $query = $this->query()->where(new WithLeftExpressionCriteria(12, 'abcd'));

        $this->assertSame('SELECT * FROM test WHERE metadata->>\'tag\' >= ? AND MD5(content) = ?', $query->toSql());
        $this->assertSame([12, 'abcd'], $query->getBindings());
    }

    public function test_with_nested()
    {
        $query = $this->query()->where(new NestedCriteria('john', new DateTime('2024-10-22')));

        $this->assertSame('SELECT * FROM test WHERE (firstName LIKE ? OR lastName LIKE ?) AND date >= ?', $query->toSql());
        $this->assertSame(['john%', 'john%', '2024-10-22 00:00:00'], $query->getBindings());
    }

    public function query(): Query
    {
        return $this->prime()->connection('test')->from('test');
    }
}
