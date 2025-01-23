<?php

namespace Bus\Query;

use Bdf\Prime\Bus\Query\FromRelation;
use Bdf\Prime\Bus\Query\PrimeQuery;
use Bdf\Prime\Bus\Query\PrimeQueryBus;
use Bdf\Prime\Customer;
use Bdf\Prime\Faction;
use Bdf\Prime\Pack;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Relations\Exceptions\RelationNotFoundException;
use Bdf\Prime\User;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class FromRelationTest extends TestCase
{
    use PrimeTestCase;

    private PrimeQueryBus $bus;

    protected function setUp(): void
    {
        $this->configurePrime();

        $this->bus = new PrimeQueryBus($this->prime());
    }

    protected function tearDown(): void
    {
        $this->unsetPrime();
        unset($this->bus);
    }

    public function test_success()
    {
        $query = new #[PrimeQuery] class {
            #[FromRelation('users')]
            public Customer $customer;
        };
        $query->customer = new Customer(['id' => 42]);

        $primeQuery = $this->bus->generateQuery($query);

        $this->assertSame('SELECT t0.* FROM user_ t0 WHERE t0.customer_id = ?', $primeQuery->toSql());
        $this->assertSame(['42'], $primeQuery->getBindings());
    }

    public function test_success_with_class_name()
    {
        $query = new #[PrimeQuery] class {
            #[FromRelation(Pack::class)]
            public Customer $customer;
        };
        $query->customer = new Customer(['id' => 42]);

        $primeQuery = $this->bus->generateQuery($query);

        $this->assertSame('SELECT t0.* FROM pack_ t0 INNER JOIN customer_pack_ packsThrough ON packsThrough.pack_id = t0.id_ WHERE packsThrough.customer_id = ?', $primeQuery->toSql());
        $this->assertSame(['42'], $primeQuery->getBindings());
    }

    public function test_missing_entity()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('requires a valid entity on property customer');

        $query = new #[PrimeQuery] class {
            #[FromRelation('users')]
            public Customer $customer;
        };

        $this->bus->generateQuery($query);
    }

    public function test_invalid_relation()
    {
        $this->expectException(RelationNotFoundException::class);
        $this->expectExceptionMessage('Relation "invalid" is not set in Bdf\Prime\Customer');

        $query = new #[PrimeQuery] class {
            #[FromRelation('invalid')]
            public Customer $customer;
        };
        $query->customer = new Customer(['id' => 42]);

        $this->bus->generateQuery($query);
    }
}
