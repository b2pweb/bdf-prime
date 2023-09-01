<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\BarConfig;
use Bdf\Prime\BaseConfig;
use Bdf\Prime\Collection\Indexer\EntityIndexer;
use Bdf\Prime\Collection\Indexer\SingleEntityIndexer;
use Bdf\Prime\Customer;
use Bdf\Prime\FooConfig;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Test\RepositoryAssertion;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class NullRelationTest extends TestCase
{
    use PrimeTestCase;
    use RepositoryAssertion;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();
    }

    /**
     *
     */
    protected function declareTestData($pack)
    {
        $pack
            ->declareEntity('Bdf\Prime\Faction')
            ->persist([
                'user' => new User([
                    'id'            => '321',
                    'name'          => 'Web User',
                    'roles'         => [1],
                    'customer'      => new Customer([
                        'id'            => '123',
                        'name'          => 'Customer',
                    ]),
                ]),
                'user2' => new User([
                    'id'            => '322',
                    'name'          => 'Web User 2',
                    'roles'         => [1],
                    'customer'      => new Customer([
                        'id'            => '123',
                        'name'          => 'Customer',
                    ]),
                ]),
                'user3' => new User([
                    'id'            => '323',
                    'name'          => 'Web User 3',
                    'roles'         => [1],
                    'customer'      => new Customer([
                        'id'            => '124',
                        'name'          => 'Customer 2',
                    ]),
                ]),
            ]);
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->primeStop();
    }

    /**
     *
     */
    public function test_load_collection()
    {
        $repository = Prime::repository('Bdf\Prime\User');

        $users = $repository->all();

        $relation = $repository->relation('none');

        $this->assertTrue($relation->isLoaded($users[0]));
        $this->assertTrue($relation->isLoaded($users[1]));
        $this->assertTrue($relation->isLoaded($users[2]));

        $relation->load(EntityIndexer::fromArray($repository->mapper(), $users));

        $this->assertNull($users[0]->none);
        $this->assertNull($users[1]->none);
        $this->assertNull($users[2]->none);

        $this->assertTrue($relation->isLoaded($users[0]));
        $this->assertTrue($relation->isLoaded($users[1]));
        $this->assertTrue($relation->isLoaded($users[2]));

    }

    /**
     *
     */
    public function test_load_single_entity()
    {
        $user = $this->getTestPack()->get('user');
        $customer = $this->getTestPack()->get('customer');

        Prime::repository('Bdf\Prime\User')
            ->relation('none')
            ->load(new SingleEntityIndexer(User::mapper(), $user));

        $this->assertNull($user->none);
        $this->assertTrue($user->relation('none')->isLoaded());
    }

    /**
     *
     */
    public function test_link_entity()
    {
        $this->expectException(\BadMethodCallException::class);
        $user = $this->getTestPack()->get('user');

        Prime::repository('Bdf\Prime\User')
            ->relation('none')
            ->link($user);
    }

    /**
     *
     */
    public function test_associate()
    {
        $user = $this->getTestPack()->get('user');

        $this->assertTrue($user->relation('none')->isLoaded());

        $user->relation('none')->associate(new \stdClass());

        $this->assertNull($user->none);
        $this->assertTrue($user->relation('none')->isLoaded());
    }

    public function test_dissociate()
    {
        $user = $this->getTestPack()->get('user');
        $user->load('none');

        $this->assertNull($user->none);
        $this->assertTrue($user->relation('none')->isLoaded());

        $user->relation('none')->dissociate();

        $this->assertNull($user->none);
        $this->assertTrue($user->relation('none')->isLoaded());
    }

    /**
     *
     */
    public function test_create()
    {
        $this->expectException(\BadMethodCallException::class);

        $user = $this->getTestPack()->get('user');

        Prime::repository('Bdf\Prime\User')
            ->relation('none')
            ->create($user, ['name' => 'Customer']);
    }

    /**
     *
     */
    public function test_save_relation()
    {
        $user = $this->getTestPack()->get('user');

        $affected = $user->relation('none')->saveAll();
        $this->assertEquals(0, $affected);
    }

    /**
     *
     */
    public function test_delete_relation()
    {
        $user = $this->getTestPack()->get('user');

        $affected = $user->relation('none')->deleteAll();
        $this->assertEquals(0, $affected);
    }

    public function test_with_inheritance()
    {
        $this->getTestPack()->nonPersist([
            $bar = new BarConfig([
                'id' => 42,
                'value' => 'bar',
            ]),
        ]);

        $entities = BaseConfig::with('extra')->all();

        $this->assertEntities([$bar], $entities);
        $this->assertNull($entities[0]->extra);
        $this->assertTrue($entities[0]->relation('extra')->isLoaded());
    }
}
