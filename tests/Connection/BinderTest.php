<?php

namespace Connection;

use Bdf\Prime\Connection\Binder;
use Bdf\Prime\PrimeTestCase;
use Doctrine\DBAL\ParameterType;
use PHPUnit\Framework\TestCase;

class BinderTest extends TestCase
{
    use PrimeTestCase;

    protected function setUp(): void
    {
        $this->primeStart();
    }

    protected function tearDown(): void
    {
        $this->unsetPrime();
    }

    public function test_bind_values()
    {
        $connection = $this->prime()->connection('test');
        $connection->executeStatement('CREATE TABLE foo (bar VARCHAR(255), baz INT, rab BOOLEAN)');

        $query = $connection->from('foo')
            ->where('bar', 'aaa')
            ->where('baz', 123)
            ->where('rab', true)
        ;

        $stmt = $connection->prepare($query);
        Binder::bindValues($stmt, $query);

        $expected = $connection->prepare($query);
        $expected->bindValue(1, 'aaa', ParameterType::STRING);
        $expected->bindValue(2, 123, ParameterType::INTEGER);
        $expected->bindValue(3, 1, ParameterType::INTEGER);

        $this->assertEquals($expected, $stmt);
    }

    public function test_types()
    {
        $this->assertSame([], Binder::types([]));
        $this->assertSame([ParameterType::STRING, ParameterType::NULL, ParameterType::INTEGER, ParameterType::STRING, ParameterType::BOOLEAN], Binder::types(['aaa', null, 125, 12.3, true]));
        $this->assertSame([
            ':a' => ParameterType::STRING,
            ':b' => ParameterType::NULL,
            ':c' => ParameterType::INTEGER,
            ':d' => ParameterType::STRING,
            ':e' => ParameterType::BOOLEAN,
        ], Binder::types([
            ':a' => 'aaa',
            ':b' => null,
            ':c' => 125,
            ':d' => 12.3,
            ':e' => true,
        ]));
    }
}
