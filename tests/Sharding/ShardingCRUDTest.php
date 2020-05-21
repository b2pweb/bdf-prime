<?php

namespace Bdf\Prime\Sharding;

use Bdf\Prime\Customer;
use Bdf\Prime\Document;
use Bdf\Prime\Exception\DBALException;
use Bdf\Prime\Location;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

/**
 * Class ShardingCRUDTest
 */
class ShardingCRUDTest extends TestCase
{
    use PrimeTestCase;

    /**
     * Basic user for tests
     *
     * @var User
     */
    protected $basicUser;

    /**
     * Basic customer for tests
     *
     * @var Customer
     */
    protected $basicCustomer;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->configurePrime();

        $this->prime()->connections()->removeConnection('test');
        $this->prime()->connections()->addConnection('test', [
            'adapter'           => 'sqlite',
            'memory'            => true,
            'dbname'            => 'TEST',
            'distributionKey'   => 'id',
            'shards'    => [
                'shard1' => ['dbname'  => 'TEST_SHARD1'],
                'shard2' => ['dbname'  => 'TEST_SHARD2'],
            ]
        ]);

        $this->primeStart();

        $this->basicUser = new User([
            'id'            => 1,
            'name'          => 'TEST1',
            'customer'      => new Customer(['id' => '1']),
            'dateInsert'    => new \DateTime(),
            'roles'         => ['2']
        ]);

        $this->basicCustomer = new Customer([
            'name'          => 'TEST',
        ]);
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->primeStop();
        $this->unsetPrime();
    }

    /**
     *
     */
    protected function declareTestData($pack)
    {
        $pack->declareEntity([User::class, Document::class]);
    }

    /**
     *
     */
    public function test_insert()
    {
        $repository = Prime::repository('Bdf\Prime\User');

        $this->assertEquals(1, $repository->insert($this->basicUser), 'method insert');
        $this->assertEquals(1, $repository->count(), 'method count');
        $this->assertEquals(1, $this->basicUser->id, 'primary is set');
        $this->assertTrue(Prime::exists($this->basicUser, false), 'entity exists');
    }

    /**
     * The order change
     */
    public function test_findOne()
    {
        $this->pack()->nonPersist([
            new User(['id' => 1, 'name' => 'TEST1', 'customer' => new Customer(['id' => '1']), 'roles' => ['2']]),
            new User(['id' => 2, 'name' => 'TEST2', 'customer' => new Customer(['id' => '1']), 'roles' => ['2']]),
            new User(['id' => 3, 'name' => 'TEST3', 'customer' => new Customer(['id' => '2']), 'roles' => ['2']]),
        ]);

        $repository = Prime::repository('Bdf\Prime\User');

        $entity = $repository->findOne([
            'name :like' => 'TEST%',
        ]);

        // 2nd entity is on first shard
        $this->assertEquals(2, $entity->id, 'id');
        $this->assertEquals(1, $entity->customer->id, 'customer.id');
    }

    /**
     *
     */
    public function test_insert_throws_duplicate_entry()
    {
        $this->expectException(DBALException::class);

        $this->pack()->nonPersist($this->basicUser);

        Prime::repository('Bdf\Prime\User')->insert($this->basicUser);
    }

    /**
     *
     */
    public function test_insert_ignore_an_existing_entity()
    {
        $this->pack()->nonPersist($this->basicUser);

        $repository = Prime::repository('Bdf\Prime\User');

        $this->assertEquals(0, $repository->insertIgnore($this->basicUser), 'method insert ignore');
        $this->assertEquals(1, $repository->count(), 'method count');
    }

    /**
     *
     */
    public function test_save_non_existing_entity()
    {
        $repository = User::repository();

        $this->assertEquals(1, $repository->save($this->basicUser), 'method save');
        $this->assertEquals(1, $repository->count(), 'method count');
    }

    /**
     *
     */
    public function test_save_existing_entity()
    {
        $this->pack()->nonPersist($this->basicUser);
        $repository = User::repository();

        $this->assertEquals(2, $repository->save($this->basicUser), 'method save');
        $this->assertEquals(1, $repository->count(), 'method count');
    }

    /**
     *
     */
    public function test_update()
    {
        $this->pack()->nonPersist($this->basicUser);
        $repository = User::repository();

        $this->basicUser->name = 'new name';

        $this->assertEquals(1, $repository->update($this->basicUser));
        $this->assertEquals($this->basicUser, User::refresh($this->basicUser));
    }

    /**
     *
     */
    public function test_update_without_change()
    {
        $this->pack()->nonPersist($this->basicUser);
        $repository = User::repository();

        $this->assertEquals(1, $repository->update($this->basicUser));
        $this->assertEquals($this->basicUser, User::refresh($this->basicUser));
    }

    /**
     *
     */
    public function test_replace_create_entity()
    {
        $repository = User::repository();

        $this->assertEquals(1, $repository->replace($this->basicUser));
        $this->assertEquals($this->basicUser, User::refresh($this->basicUser));
    }

    /**
     *
     */
    public function test_replace_update_entity()
    {
        $this->pack()->nonPersist($this->basicUser);
        $repository = User::repository();

        $this->basicUser->name = 'new name';

        $this->assertEquals(2, $repository->replace($this->basicUser));
        $this->assertEquals($this->basicUser, User::refresh($this->basicUser));
    }

    /**
     *
     */
    public function test_find()
    {
        $this->pack()->nonPersist([
            new User(['id' => 1, 'name' => 'TEST1', 'customer' => new Customer(['id' => '1']), 'roles' => ['2']]),
            new User(['id' => 2, 'name' => 'TEST2', 'customer' => new Customer(['id' => '1']), 'roles' => ['2']]),
        ]);

        $result = Prime::repository('Bdf\Prime\User')->find([
            'customer.id' => 1,
            ':limit'      => 3,
            ':order'      => 'id',
        ]);

        $this->assertEquals(2, count($result), 'method count');
    }

    /**
     *
     */
    public function test_refresh()
    {
        $repository = User::repository();
        $this->assertNull($repository->refresh($this->basicUser));

        $this->pack()->nonPersist($this->basicUser);
        $this->assertEquals($this->basicUser, $repository->refresh($this->basicUser));

        $newUser = clone $this->basicUser;
        $newUser->name = 'new name';
        $repository->update($newUser);

        $this->assertEquals($newUser, $repository->refresh($this->basicUser));
    }

    /**
     *
     */
    public function test_exists()
    {
        $this->pack()->nonPersist([
            new User(['id' => 1, 'name' => 'TEST1', 'customer' => new Customer(['id' => '1']), 'roles' => ['2']]),
            new User(['id' => 2, 'name' => 'TEST2', 'customer' => new Customer(['id' => '1']), 'roles' => ['2']]),
            new User(['id' => 3, 'name' => 'TEST3', 'customer' => new Customer(['id' => '2']), 'roles' => ['2']]),
        ]);

        $repository = User::repository();

        $this->assertTrue($repository->exists(new User(['id' => 1])));
        $this->assertTrue($repository->exists(new User(['id' => 2])));
        $this->assertTrue($repository->exists(new User(['id' => 3])));
        $this->assertFalse($repository->exists(new User(['id' => 42])));
    }

    /**
     *
     */
    public function test_multiple_embedded()
    {
        $this->pack()->nonPersist(
            Document::entity([
                'id' => 1,
                'customerId'   => '10',
                'uploaderType' => 'user',
                'uploaderId'   => '1',
                'contact' => (object)[
                    'name'     => 'Holmes',
                    'location' => new Location([
                        'address' => '221b Baker Street',
                        'city'    => 'London',
                    ])
                ],
            ])
        );

        $document = Document::get(1);

        $this->assertInstanceOf('Bdf\Prime\Contact', $document->contact);
        $this->assertInstanceOf('Bdf\Prime\Location', $document->contact->location);

        $this->assertEquals('Holmes', $document->contact->name);
        $this->assertEquals('221b Baker Street', $document->contact->location->address);
        $this->assertEquals('London', $document->contact->location->city);
    }
}
