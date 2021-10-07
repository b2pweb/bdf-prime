<?php

namespace Sharding;

use Bdf\Prime\Sharding\MultiResult;
use Doctrine\DBAL\Cache\ArrayResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\TestCase;

/**
 * Class MultiResultTest
 */
class MultiResultTest extends TestCase
{
    /**
     *
     */
    public function test_empty()
    {
        $result = new MultiResult([]);

        $this->assertSame(0, $result->columnCount());
        $this->assertSame(0, $result->rowCount());
        $this->assertFalse($result->fetchNumeric());
        $this->assertFalse($result->fetchAssociative());
        $this->assertFalse($result->fetchOne());
        $this->assertSame([], $result->fetchAllNumeric());
        $this->assertSame([], $result->fetchAllAssociative());
        $this->assertSame([], $result->fetchFirstColumn());
        $this->assertSame([], iterator_to_array($result->getIterator()));
    }

    /**
     *
     */
    public function test_single_result()
    {
        $connection = $this->createMock(Connection::class);
        $result = new MultiResult([new Result(new ArrayResult([
            ['firstName' => 'Bob', 'lastName' => 'Morison'],
            ['firstName' => 'Albert', 'lastName' => 'Rutherford'],
            ['firstName' => 'Jean', 'lastName' => 'Dupont'],
        ]), $connection)]);

        $this->assertSame(2, $result->columnCount());
        $this->assertSame(3, $result->rowCount());
        $this->assertSame(['Bob', 'Morison'], $result->fetchNumeric());
        $this->assertSame(['firstName' => 'Albert', 'lastName' => 'Rutherford'], $result->fetchAssociative());
        $this->assertSame('Jean', $result->fetchOne());
        $this->assertFalse($result->fetchAssociative());
    }

    /**
     *
     */
    public function test_single_result_fetchAllNumeric()
    {
        $connection = $this->createMock(Connection::class);
        $result = new MultiResult([new Result(new ArrayResult([
            ['firstName' => 'Bob', 'lastName' => 'Morison'],
            ['firstName' => 'Albert', 'lastName' => 'Rutherford'],
            ['firstName' => 'Jean', 'lastName' => 'Dupont'],
        ]), $connection)]);

        $this->assertSame([
            ['Bob', 'Morison'],
            ['Albert', 'Rutherford'],
            ['Jean', 'Dupont'],
        ], $result->fetchAllNumeric());
    }

    /**
     *
     */
    public function test_single_result_fetchAllAssociative()
    {
        $connection = $this->createMock(Connection::class);
        $result = new MultiResult([new Result(new ArrayResult([
            ['firstName' => 'Bob', 'lastName' => 'Morison'],
            ['firstName' => 'Albert', 'lastName' => 'Rutherford'],
            ['firstName' => 'Jean', 'lastName' => 'Dupont'],
        ]), $connection)]);

        $this->assertSame([
            ['firstName' => 'Bob', 'lastName' => 'Morison'],
            ['firstName' => 'Albert', 'lastName' => 'Rutherford'],
            ['firstName' => 'Jean', 'lastName' => 'Dupont'],
        ], $result->fetchAllAssociative());
    }

    /**
     *
     */
    public function test_single_result_fetchFirstColumn()
    {
        $connection = $this->createMock(Connection::class);
        $result = new MultiResult([new Result(new ArrayResult([
            ['firstName' => 'Bob', 'lastName' => 'Morison'],
            ['firstName' => 'Albert', 'lastName' => 'Rutherford'],
            ['firstName' => 'Jean', 'lastName' => 'Dupont'],
        ]), $connection)]);

        $this->assertSame(['Bob', 'Albert', 'Jean'], $result->fetchFirstColumn());
    }

    /**
     *
     */
    public function test_single_result_iterator()
    {
        $connection = $this->createMock(Connection::class);
        $result = new MultiResult([new Result(new ArrayResult([
            ['firstName' => 'Bob', 'lastName' => 'Morison'],
            ['firstName' => 'Albert', 'lastName' => 'Rutherford'],
            ['firstName' => 'Jean', 'lastName' => 'Dupont'],
        ]), $connection)]);

        $this->assertSame([
            ['firstName' => 'Bob', 'lastName' => 'Morison'],
            ['firstName' => 'Albert', 'lastName' => 'Rutherford'],
            ['firstName' => 'Jean', 'lastName' => 'Dupont'],
        ], iterator_to_array($result));
    }

    /**
     *
     */
    public function test_multi_result()
    {
        $connection = $this->createMock(Connection::class);
        $result = new MultiResult();
        $result->add(new Result(new ArrayResult([
            ['firstName' => 'Bob', 'lastName' => 'Morison'],
        ]), $connection));
        $result->add(new Result(new ArrayResult([
            ['firstName' => 'Albert', 'lastName' => 'Rutherford'],
            ['firstName' => 'Jean', 'lastName' => 'Dupont'],
        ]), $connection));

        $this->assertSame(2, $result->columnCount());
        $this->assertSame(3, $result->rowCount());
        $this->assertSame(['Bob', 'Morison'], $result->fetchNumeric());
        $this->assertSame(['firstName' => 'Albert', 'lastName' => 'Rutherford'], $result->fetchAssociative());
        $this->assertSame('Jean', $result->fetchOne());
        $this->assertFalse($result->fetchAssociative());
    }

    /**
     *
     */
    public function test_multi_results_fetchAllNumeric()
    {
        $connection = $this->createMock(Connection::class);
        $result = new MultiResult();
        $result->add(new Result(new ArrayResult([
            ['firstName' => 'Bob', 'lastName' => 'Morison'],
        ]), $connection));
        $result->add(new Result(new ArrayResult([
            ['firstName' => 'Albert', 'lastName' => 'Rutherford'],
            ['firstName' => 'Jean', 'lastName' => 'Dupont'],
        ]), $connection));

        $this->assertSame([
            ['Bob', 'Morison'],
            ['Albert', 'Rutherford'],
            ['Jean', 'Dupont'],
        ], $result->fetchAllNumeric());
    }

    /**
     *
     */
    public function test_multi_results_fetchAllAssociative()
    {
        $connection = $this->createMock(Connection::class);
        $result = new MultiResult();
        $result->add(new Result(new ArrayResult([
            ['firstName' => 'Bob', 'lastName' => 'Morison'],
        ]), $connection));
        $result->add(new Result(new ArrayResult([
            ['firstName' => 'Albert', 'lastName' => 'Rutherford'],
            ['firstName' => 'Jean', 'lastName' => 'Dupont'],
        ]), $connection));

        $this->assertSame([
            ['firstName' => 'Bob', 'lastName' => 'Morison'],
            ['firstName' => 'Albert', 'lastName' => 'Rutherford'],
            ['firstName' => 'Jean', 'lastName' => 'Dupont'],
        ], $result->fetchAllAssociative());
    }

    /**
     *
     */
    public function test_multi_results_fetchFirstColumn()
    {
        $connection = $this->createMock(Connection::class);
        $result = new MultiResult();
        $result->add(new Result(new ArrayResult([
            ['firstName' => 'Bob', 'lastName' => 'Morison'],
        ]), $connection));
        $result->add(new Result(new ArrayResult([
            ['firstName' => 'Albert', 'lastName' => 'Rutherford'],
            ['firstName' => 'Jean', 'lastName' => 'Dupont'],
        ]), $connection));

        $this->assertSame(['Bob', 'Albert', 'Jean'], $result->fetchFirstColumn());
    }

    /**
     *
     */
    public function test_multi_results_iterator()
    {
        $connection = $this->createMock(Connection::class);
        $result = new MultiResult();
        $result->add(new Result(new ArrayResult([
            ['firstName' => 'Bob', 'lastName' => 'Morison'],
        ]), $connection));
        $result->add(new Result(new ArrayResult([
            ['firstName' => 'Albert', 'lastName' => 'Rutherford'],
            ['firstName' => 'Jean', 'lastName' => 'Dupont'],
        ]), $connection));

        $this->assertSame([
            ['firstName' => 'Bob', 'lastName' => 'Morison'],
            ['firstName' => 'Albert', 'lastName' => 'Rutherford'],
            ['firstName' => 'Jean', 'lastName' => 'Dupont'],
        ], iterator_to_array($result));
    }
}
