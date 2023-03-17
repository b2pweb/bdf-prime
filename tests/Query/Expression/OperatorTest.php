<?php

namespace Query\Expression;

use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Operator;
use Bdf\Prime\Query\Query;
use Bdf\Prime\Task;
use PHPUnit\Framework\TestCase;

class OperatorTest extends TestCase
{
    use PrimeTestCase;

    protected function setUp(): void
    {
        $this->configurePrime();
    }

    protected function tearDown(): void
    {
        $this->unsetPrime();
    }

    public function test_operator_as_string()
    {
        $this->assertEquals('SELECT * FROM test WHERE id >= 5', $this->query()->where('id', Operator::{'>='}(5))->toRawSql());
        $this->assertEquals('SELECT * FROM test WHERE id IN (5,8,9)', $this->query()->where('id', Operator::{'in'}(5, 8, 9))->toRawSql());
    }

    public function test_operators()
    {
        $this->assertEquals('SELECT * FROM test WHERE id < 5', $this->query()->where('id', Operator::lessThan(5))->toRawSql());
        $this->assertEquals('SELECT * FROM test WHERE id <= 5', $this->query()->where('id', Operator::lessThanOrEqual(5))->toRawSql());
        $this->assertEquals('SELECT * FROM test WHERE id > 5', $this->query()->where('id', Operator::greaterThan(5))->toRawSql());
        $this->assertEquals('SELECT * FROM test WHERE id >= 5', $this->query()->where('id', Operator::greaterThanOrEqual(5))->toRawSql());
        $this->assertEquals('SELECT * FROM test WHERE id REGEXP \'[a-z0-9]+\'', $this->query()->where('id', Operator::regex('[a-z0-9]+'))->toRawSql());
        $this->assertEquals('SELECT * FROM test WHERE id LIKE \'123%\'', $this->query()->where('id', Operator::like('123%'))->toRawSql());
        $this->assertEquals('SELECT * FROM test WHERE id NOT LIKE \'123%\'', $this->query()->where('id', Operator::notlike('123%'))->toRawSql());
        $this->assertEquals('SELECT * FROM test WHERE id IN (5,8,3)', $this->query()->where('id', Operator::in(5, 8, 3))->toRawSql());
        $this->assertEquals('SELECT * FROM test WHERE id NOT IN (5,8,3)', $this->query()->where('id', Operator::notIn(5, 8, 3))->toRawSql());
        $this->assertEquals('SELECT * FROM test WHERE id BETWEEN 5 AND 8', $this->query()->where('id', Operator::between(5, 8))->toRawSql());
        $this->assertEquals('SELECT * FROM test WHERE NOT(id BETWEEN 5 AND 8)', $this->query()->where('id', Operator::notBetween(5, 8))->toRawSql());
        $this->assertEquals('SELECT * FROM test WHERE id != 5', $this->query()->where('id', Operator::notEqual(5))->toRawSql());
    }

    public function test_should_convert_value_to_database()
    {
        $this->assertEquals('SELECT * FROM test WHERE date_insert < \'2022-05-10 00:00:00\'', $this->query()->where('date_insert', Operator::lessThan(new \DateTime('2022-05-10')))->toRawSql());
        $this->assertEquals('SELECT t0.* FROM with_timestamp t0 WHERE t0.created_at > 1652187600', EntityWithTimestamp::where('createdAt', Operator::{'>'}(new \DateTime('2022-05-10 15:00:00')))->toRawSql());
    }

    public function query(): Query
    {
        return $this->prime()->connection('test')->from('test');
    }
}

class EntityWithTimestamp extends Model
{
    public $createdAt;
}

class EntityWithTimestampMapper extends Mapper
{
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table' => 'with_timestamp',
        ];
    }

    public function buildFields(FieldBuilder $builder): void
    {
        $builder->timestamp('createdAt')->alias('created_at')->nillable();
    }
}
