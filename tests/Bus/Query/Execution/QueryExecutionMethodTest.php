<?php

namespace Bus\Query\Execution;

use Bdf\Prime\Bus\Query\Execution\QueryExecutionMethod;
use Bdf\Prime\Bus\Query\PrimeQuery;
use Bdf\Prime\Bus\Query\PrimeQueryBus;
use Bdf\Prime\Customer;
use Bdf\Prime\Exception\EntityNotFoundException;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Criteria\Criterion;
use Bdf\Prime\Query\Pagination\Paginator;
use Bdf\Prime\Query\Pagination\Walker;
use Bdf\Prime\Test\TestPack;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

class QueryExecutionMethodTest extends TestCase
{
    use PrimeTestCase;

    private PrimeQueryBus $bus;
    private TestPack $testPack;

    protected function setUp(): void
    {
        $this->primeStart();

        $this->prime()->connections()->declareConnection('other', 'sqlite::memory:');

        $this->bus = new PrimeQueryBus($this->prime());
        $this->testPack = $this->pack();
    }

    protected function tearDown(): void
    {
        $this->primeStop();
        $this->unsetPrime();
        unset($this->bus);
        unset($this->testPack);
    }

    public function declareTestData(TestPack $testPack)
    {
        $testPack->persist([
            'user1' => new User([
                'id' => 1,
                'name' => 'John Doe',
                'customer' => new Customer(['id' => 1]),
                'roles' => ['admin', 'user'],
            ]),
            'user2' => new User([
                'id' => 2,
                'name' => 'Jane Smith',
                'customer' => new Customer(['id' => 1]),
                'roles' => ['user'],
            ]),
            'user3' => new User([
                'id' => 3,
                'name' => 'Joan Paul',
                'customer' => new Customer(['id' => 2]),
                'roles' => ['admin'],
            ]),
        ]);
    }

    public function test_all()
    {
        $dto = new #[PrimeQuery(User::class, method: QueryExecutionMethod::All)] class {};
        $results = $this->bus->query($dto);

        $this->assertIsArray($results);
        $this->assertContainsOnly(User::class, $results);
        $this->assertCount(3, $results);

        $this->assertEquals([$this->testPack->get('user1'), $this->testPack->get('user2'), $this->testPack->get('user3')], $results);
    }

    public function test_walk()
    {
        $dto = new #[PrimeQuery(User::class, method: QueryExecutionMethod::Walk)] class {};
        $results = $this->bus->query($dto);

        $this->assertInstanceOf(Walker::class, $results);

        $results = iterator_to_array($results);
        $this->assertContainsOnly(User::class, $results);
        $this->assertCount(3, $results);

        $this->assertEquals([$this->testPack->get('user1'), $this->testPack->get('user2'), $this->testPack->get('user3')], $results);
    }

    public function test_paginate()
    {
        $dto = new #[PrimeQuery(User::class, method: QueryExecutionMethod::Paginate)] class {};
        $results = $this->bus->query($dto);

        $this->assertInstanceOf(Paginator::class, $results);

        $results = iterator_to_array($results);
        $this->assertContainsOnly(User::class, $results);
        $this->assertCount(3, $results);

        $this->assertEquals([$this->testPack->get('user1'), $this->testPack->get('user2'), $this->testPack->get('user3')], $results);
    }

    public function test_first()
    {
        $dto = new #[PrimeQuery(User::class, method: QueryExecutionMethod::First)] class {};
        $result = $this->bus->query($dto);
        $this->assertEquals($this->testPack->get('user1'), $result);

        $dto = new #[PrimeQuery(User::class, method: QueryExecutionMethod::First)] class {
            #[Criterion]
            public int $id = 42;
        };
        $result = $this->bus->query($dto);
        $this->assertNull($result);
    }

    public function test_first_or_fail()
    {
        $dto = new #[PrimeQuery(User::class, method: QueryExecutionMethod::FirstOrFail)] class {};
        $result = $this->bus->query($dto);
        $this->assertEquals($this->testPack->get('user1'), $result);

        $dto = new #[PrimeQuery(User::class, method: QueryExecutionMethod::FirstOrFail)] class {
            #[Criterion]
            public int $id = 42;
        };

        try {
            $result = $this->bus->query($dto);
            $this->fail('Expects EntityNotFoundException');
        } catch (EntityNotFoundException $e) {
            $this->assertStringContainsString('Cannot resolve entity', $e->getMessage());
        }
    }

    public function test_first_or_new()
    {
        $dto = new #[PrimeQuery(User::class, method: QueryExecutionMethod::FirstOrNew)] class {};
        $result = $this->bus->query($dto);
        $this->assertEquals($this->testPack->get('user1'), $result);

        $dto = new #[PrimeQuery(User::class, method: QueryExecutionMethod::FirstOrNew)] class {
            #[Criterion]
            public int $id = 42;
        };
        $result = $this->bus->query($dto);
        $this->assertEquals(new User(['id' => 42]), $result);
    }

    public function test_count()
    {
        $dto = new #[PrimeQuery(User::class, method: QueryExecutionMethod::Count)] class {};
        $this->assertSame(3, $this->bus->query($dto));
    }
}
