<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\Collection\Indexer\EntityIndexer;
use Bdf\Prime\Collection\Indexer\SingleEntityIndexer;
use Bdf\Prime\Commit;
use Bdf\Prime\Company;
use Bdf\Prime\Developer;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Admin;
use Bdf\Prime\Customer;
use Bdf\Prime\Document;
use Bdf\Prime\Project;
use Bdf\Prime\User;
use Bdf\Prime\Faction;
use Bdf\Prime\Test\RepositoryAssertion;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class BelongsToTest extends TestCase
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
                'admin' => new Admin([
                    'id'            => '1',
                    'name'          => 'Admin User',
                    'roles'         => [1],
                ]),

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

                'customer' => new Customer([
                    'id'            => '123',
                    'name'          => 'Customer',
                ]),
                'customer2' => new Customer([
                    'id'            => '124',
                    'name'          => 'Customer 2',
                ]),

                'document-admin' => new Document([
                    'id'             => '1',
                    'customerId'     => '123',
                    'uploaderType'   => 'admin',
                    'uploaderId'     => '1',
                ]),

                'document-user' => new Document([
                    'id'             => '2',
                    'customerId'     => '123',
                    'uploaderType'   => 'user',
                    'uploaderId'     => '321',
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
        $customer = $this->getTestPack()->get('customer');
        $customer2 = $this->getTestPack()->get('customer2');

        $users = $repository->all();

        $relation = $repository->relation('customer');

        $this->assertFalse($relation->isLoaded($users[0]));
        $this->assertFalse($relation->isLoaded($users[1]));
        $this->assertFalse($relation->isLoaded($users[2]));

        $relation->load(EntityIndexer::fromArray($repository->mapper(), $users));

        $this->assertEquals($customer, $users[0]->customer, 'customer on user 1');
        $this->assertEquals($customer, $users[1]->customer, 'customer on user 2');
        $this->assertEquals($customer2, $users[2]->customer, 'customer on user 3');

        $this->assertTrue($relation->isLoaded($users[0]));
        $this->assertTrue($relation->isLoaded($users[1]));
        $this->assertTrue($relation->isLoaded($users[2]));

    }

    /**
     *
     */
    public function test_load_collection_with_sub_relations()
    {
        $repository = Prime::repository('Bdf\Prime\User');
        $document1 = $this->getTestPack()->get('document-admin');
        $document2 = $this->getTestPack()->get('document-user');

        $users = $repository->all();

        $relation = $repository->relation('customer');
        $relation->load(EntityIndexer::fromArray($repository->mapper(), $users), ['documents']);

        $this->assertEquals([$document1, $document2], $users[0]->customer->documents);

        $this->assertTrue($relation->isLoaded($users[0]));
        $this->assertTrue($relation->isLoaded($users[1]));
        $this->assertTrue($relation->isLoaded($users[2]));
    }

    /**
     *
     */
    public function test_load_collection_with_constraints()
    {
        $repository = Prime::repository('Bdf\Prime\User');
        $customer = $this->getTestPack()->get('customer2');

        $users = $repository->all();

        $relation = $repository->relation('customer');
        $relation->load(EntityIndexer::fromArray($repository->mapper(), $users), [], ['id :not' => '123']);

        $this->assertNull($users[0]->customer->name);
        $this->assertNull($users[1]->customer->name);
        $this->assertEquals($customer->name, $users[2]->customer->name);

        $this->assertFalse($relation->isLoaded($users[0]));
        $this->assertFalse($relation->isLoaded($users[1]));
        $this->assertTrue($relation->isLoaded($users[2]));
    }

    /**
     *
     */
    public function test_dynamic_join()
    {
        $users = Prime::repository('Bdf\Prime\User')
            ->where('customer.name', 'Customer 2')
            ->all();

        $this->assertEquals('323', $users[0]->id);
    }

    /**
     *
     */
    public function test_far_dynamic_join()
    {
        $users = Prime::repository('Bdf\Prime\User')
            ->where('customer.documents.uploaderType', 'admin')
            ->all();

        $this->assertEquals('321', $users[0]->id);
    }

    /**
     *
     */
    public function test_dynamic_join_path()
    {
        Prime::push($faction = new Faction([
            'id'     => 1,
            'name'   => 'test-faction',
            'domain' => 'user',
            'enabled' => true,
        ]));

        $user = $this->getTestPack()->get('user');
        $user->faction = $faction;
        Prime::push($user);

        $query = Customer::where('documents.uploader#user.faction.id', '1');
        $this->assertEquals(2, substr_count($query->toRawSql(), 'INNER JOIN'));

        $customers = $query->all();
        $this->assertEquals('123', $customers[0]->id);

        $query = Customer::where('documents.uploader#user.faction>id', '1');
        $this->assertEquals(3, substr_count($query->toRawSql(), 'INNER JOIN'));

        $customers = $query->all();
        $this->assertEquals('123', $customers[0]->id);
    }

    /**
     *
     */
    public function test_load_single_entity()
    {
        $user = $this->getTestPack()->get('user');
        $customer = $this->getTestPack()->get('customer');

        Prime::repository('Bdf\Prime\User')
            ->relation('customer')
            ->load(new SingleEntityIndexer(User::mapper(), $user));

        $this->assertEquals($customer->name, $user->customer->name);
        $this->assertTrue($user->relation('customer')->isLoaded());
    }

    /**
     *
     */
    public function test_link_entity()
    {
        $user = $this->getTestPack()->get('user');

        $customer = Prime::repository('Bdf\Prime\User')
            ->relation('customer')
            ->link($user)
            ->first();

        $this->assertEquals($this->getTestPack()->get('customer')->name, $customer->name);
    }

    /**
     *
     */
    public function test_associate()
    {
        $user = $this->getTestPack()->get('user');

        $customer = new Customer([
            'id'   => 100,
            'name' => 'Customer associated'
        ]);

        $this->assertFalse($user->relation('customer')->isLoaded());

        $user->relation('customer')->associate($customer);

        $this->assertEquals(100, $user->customer->id);
        $this->assertEquals($customer, $user->customer);
        $this->assertTrue($user->relation('customer')->isLoaded());
    }

    /**
     * @group seb
     */
    public function test_dissociate()
    {
        $user = $this->getTestPack()->get('user');
        $user->load('customer');
        $customer = $user->customer;

        $this->assertNotNull($customer);
        $this->assertNotNull($customer->id);

        $this->assertTrue($user->relation('customer')->isLoaded());

        $user->relation('customer')->dissociate();

        $this->assertNull($user->customer);
        $this->assertFalse($user->relation('customer')->isLoaded());
        // Uncomment when the fix on embedded will be done
//        $this->assertNotNull($customer->id);
    }

    /**
     *
     */
    public function test_create()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The local entity is not the primary key barrier.');

        $user = $this->getTestPack()->get('user');

        Prime::repository('Bdf\Prime\User')
            ->relation('customer')
            ->create($user, ['name' => 'Customer']);

        $this->assertTrue($user->relation('customer')->isLoaded());
    }

    /**
     *
     */
    public function test_save_relation()
    {
        $user = $this->getTestPack()->get('user');
        $user->customer = new Customer([
            'name' => 'relation'
        ]);

        $affected = $user->relation('customer')->saveAll();

        $nb = Customer::where('name', 'relation')->count();

        $this->assertEquals(1, $nb);
        $this->assertEquals(1, $affected);
    }

    /**
     *
     */
    public function test_delete_relation()
    {
        $user = $this->getTestPack()->get('user');
        $user->load('customer');

        $affected = $user->relation('customer')->deleteAll();

        $customer = Customer::get($user->customer->id);

        $this->assertEquals(1, $affected);
        $this->assertEquals(null, $customer);
    }

    /**
     *
     */
    public function test_load_with_constraints()
    {
        Prime::push('Bdf\Prime\Admin', [
            'id'    => 999,
            'name'  => 'test',
            'roles' => [],
            'faction' => new Faction(['id' => 1]),
        ]);

        $faction = new Faction([
            'id'     => 1,
            'name'   => 'test-faction',
            'domain' => 'admin',
            'enabled' => true,
        ]);
        Prime::push($faction);

        $user = Prime::repository('Bdf\Prime\Admin')->with('faction')->get('999');
        $this->assertEquals('test-faction', $user->faction->name);
        $this->assertTrue($user->relation('faction')->isLoaded());

        $faction->domain = 'non-admin';
        Prime::repository('Bdf\Prime\Faction')->update($faction);

        $user = Prime::repository('Bdf\Prime\Admin')->with('faction')->get('999');
        $this->assertEquals(null, $user->faction->name);
        $this->assertFalse($user->relation('faction')->isLoaded());
    }

    /**
     *
     */
    public function test_load_with_repository_constraints()
    {
        Prime::push('Bdf\Prime\Admin', [
            'id'    => 999,
            'name'  => 'test',
            'roles' => [],
            'faction' => new Faction(['id' => 1]),
        ]);
        $faction = new Faction([
            'id'     => 1,
            'name'   => 'test-faction',
            'domain' => 'admin',
            'enabled' => true,
        ]);
        Prime::push($faction);

        $user = Prime::repository('Bdf\Prime\Admin')->with('faction')->get('999');
        $this->assertEquals('test-faction', $user->faction->name);
        $this->assertTrue($user->relation('faction')->isLoaded());

        $faction->enabled = false;
        Prime::repository('Bdf\Prime\Faction')->update($faction);

        $user = Prime::repository('Bdf\Prime\Admin')->with('faction')->get('999');
        $this->assertEquals(null, $user->faction->name);
        $this->assertFalse($user->relation('faction')->isLoaded());
    }

    /**
     *
     */
    public function test_join_with_constraints()
    {
        Prime::push('Bdf\Prime\Admin', [
            'id'    => 999,
            'name'  => 'test',
            'roles' => [],
            'faction' => new Faction(['id' => 1]),
        ]);
        $faction = new Faction([
            'id'     => 1,
            'name'   => 'test-faction',
            'domain' => 'admin',
            'enabled' => true,
        ]);
        Prime::push($faction);

        $user = Prime::repository('Bdf\Prime\Admin')
            ->where('faction.name', 'test-faction')
            ->first();

        $this->assertEquals(999, $user->id);

        $faction->domain = 'non-admin';
        Prime::repository('Bdf\Prime\Faction')->update($faction);

        $user = Prime::repository('Bdf\Prime\Admin')
            ->where('faction.name', 'test-faction')
            ->first();

        $this->assertEquals(null, $user);
    }

    /**
     *
     */
    public function test_join_with_repository_constraints()
    {
        Prime::push('Bdf\Prime\Admin', [
            'id'    => 999,
            'name'  => 'test',
            'roles' => [],
            'faction' => new Faction(['id' => 1]),
        ]);
        $faction = new Faction([
            'id'     => 1,
            'name'   => 'test-faction',
            'domain' => 'admin',
            'enabled' => true,
        ]);
        Prime::push($faction);

        $user = Prime::repository('Bdf\Prime\Admin')
            ->where('faction.name', 'test-faction')
            ->first();

        $this->assertEquals(999, $user->id);

        $faction->enabled = false;
        Prime::repository('Bdf\Prime\Faction')->update($faction);

        $user = Prime::repository('Bdf\Prime\Admin')
            ->where('faction.name', 'test-faction')
            ->first();

        $this->assertEquals(null, $user);
    }


    /**
     *
     */
    public function test_constraints_on_multi_join()
    {
        Prime::push('Bdf\Prime\User', [
            'id'    => 999,
            'name'  => 'test-user',
            'roles' => [],
            'customer' => new Customer(['id' => 1]),
            'faction' => new Faction(['id' => 1]),
        ]);

        Prime::push('Bdf\Prime\Customer', [
            'id'    => 1,
            'name'  => 'test-customer',
        ]);

        Prime::push($faction = new Faction([
            'id'     => 1,
            'name'   => 'test-faction',
            'domain' => 'user',
            'enabled' => true,
        ]));

        $customer = Prime::repository('Bdf\Prime\Customer')
            ->where('users.faction.name', 'test-faction')
            ->first();

        $this->assertEquals('test-customer', $customer->name);

        $faction->enabled = false;
        Prime::repository('Bdf\Prime\Faction')->update($faction);

        $customer = Prime::repository('Bdf\Prime\Customer')
            ->where('users.faction.name', 'test-faction')
            ->first();
        $this->assertEquals(null, $customer);

        $faction->domain = 'admin';
        $faction->enabled = true;
        Prime::repository('Bdf\Prime\Faction')->update($faction);

        $customer = Prime::repository('Bdf\Prime\Customer')
            ->where('users.faction.name', 'test-faction')
            ->first();
        $this->assertEquals(null, $customer);
    }

    /**
     *
     */
    public function test_load_collection_without_subrelation()
    {
        $this->getTestPack()->nonPersist([
            'project' => new Project([
                'id' => 1,
                'name' => 'Projet 1'
            ]),
            'company' => new Company([
                'id' => 1,
                'name' => 'Société 1'
            ])
        ])
            ->nonPersist([
                'author' => new Developer([
                    'id' => 1,
                    'name' => 'Dév 1',
                    'project' => $this->getTestPack()->get('project'),
                    'company' => $this->getTestPack()->get('company')
                ])
            ])
            ->nonPersist([
                'commit' => new Commit([
                    'id' => 1,
                    'message' => 'Message de commit',
                    'project' => $this->getTestPack()->get('project'),
                    'authorId' => 1,
                    'authorType' => 'developer',
                ])
            ]);

        $repository = Prime::repository('Bdf\Prime\Commit');

        $commits = $repository->all();

        $relation = $repository->relation('project');
        $relation->load(EntityIndexer::fromArray($repository->mapper(), $commits), [], [], ['commits']);

        $this->assertEmpty($commits[0]->project->commits);
        $this->assertTrue($commits[0]->relation('project')->isLoaded());
    }

    /**
     *
     */
    public function test_load_without_subrelation()
    {
        $this->getTestPack()->nonPersist([
            'project' => new Project([
                'id' => 1,
                'name' => 'Projet 1'
            ]),
            'company' => new Company([
                'id' => 1,
                'name' => 'Société 1'
            ])
        ])
            ->nonPersist([
                'author' => new Developer([
                    'id' => 1,
                    'name' => 'Dév 1',
                    'project' => $this->getTestPack()->get('project'),
                    'company' => $this->getTestPack()->get('company')
                ])
            ])
            ->nonPersist([
                'commit' => new Commit([
                    'id' => 1,
                    'message' => 'Message de commit',
                    'project' => $this->getTestPack()->get('project'),
                    'authorId' => 1,
                    'authorType' => 'developer',
                ])
            ]);

        $repository = Prime::repository('Bdf\Prime\Commit');

        $commit = $repository->get(1);

        $relation = $repository->relation('project');
        $relation->load(new SingleEntityIndexer(Commit::mapper(), $commit), [], [], ['commits']);

        $this->assertEmpty($commit->project->commits);
    }

    /**
     *
     */
    public function test_load_should_not_reload_on_second_call()
    {
        $user = new Admin([
            'id'    => 999,
            'name'  => 'test',
            'roles' => [],
            'faction' => new Faction(['id' => 1]),
        ]);
        $user->insert();

        (new Faction([
            'id'     => 1,
            'name'   => 'test-faction',
            'domain' => 'admin',
            'enabled' => true,
        ]))->insert();

        $this->assertFalse($user->relation('faction')->isLoaded());
        $user->load('faction');
        $this->assertTrue($user->relation('faction')->isLoaded());

        $faction = $user->faction;

        $user->load('faction');
        $this->assertSame($faction, $user->faction);
    }

    /**
     *
     */
    public function test_load_with_subrelation_on_second_call_should_only_load_the_subrelation()
    {
        $this->getTestPack()->nonPersist([
            $project = new Project([
                'id' => 1,
                'name' => 'Projet 1'
            ]),
            'company' => new Company([
                'id' => 1,
                'name' => 'Société 1'
            ])
        ])
            ->nonPersist([
                $dev = new Developer([
                    'id' => 1,
                    'name' => 'Dév 1',
                    'project' => $project,
                    'company' => $this->getTestPack()->get('company')
                ])
            ])
            ->nonPersist([
                $commit = new Commit([
                    'id' => 1,
                    'message' => 'Message de commit',
                    'project' => $project,
                    'authorId' => 1,
                    'authorType' => 'developer',
                ])
            ]);

        $commit->load('author');
        $this->assertTrue($commit->relation('author')->isLoaded());
        $this->assertEntity($dev, $commit->author);

        $loadedAuthor = $commit->author;
        $commit->load('author.project');

        $this->assertSame($loadedAuthor, $commit->author);
        $this->assertEntity($project, $commit->author->project);
    }

    /**
     *
     */
    public function test_load_already_loaded_with_subrelation_without()
    {
        $this->getTestPack()->nonPersist([
            $project = new Project([
                'id' => 1,
                'name' => 'Projet 1'
            ]),
            'company' => new Company([
                'id' => 1,
                'name' => 'Société 1'
            ])
        ])
            ->nonPersist([
                $dev = new Developer([
                    'id' => 1,
                    'name' => 'Dév 1',
                    'project' => $project,
                    'company' => $this->getTestPack()->get('company')
                ])
            ])
            ->nonPersist([
                $commit = new Commit([
                    'id' => 1,
                    'message' => 'Message de commit',
                    'project' => $project,
                    'authorId' => 1,
                    'authorType' => 'developer',
                ])
            ])
        ;

        Commit::repository()->relation('author')->loadIfNotLoaded(new SingleEntityIndexer(Commit::mapper(), $commit), ['project'], [], ['project.commits', 'project.developers']);

        $loadedAuthor = $commit->author;
        $commit->load('author.project');

        $this->assertSame($loadedAuthor, $commit->author);
        $this->assertEntity($project, $commit->author->project);
    }

    /**
     *
     */
    public function test_reload()
    {
        $this->getTestPack()->nonPersist([
            $project = new Project([
                'id' => 1,
                'name' => 'Projet 1'
            ]),
            'company' => new Company([
                'id' => 1,
                'name' => 'Société 1'
            ])
        ])
            ->nonPersist([
                $dev = new Developer([
                    'id' => 1,
                    'name' => 'Dév 1',
                    'project' => $project,
                    'company' => $this->getTestPack()->get('company')
                ])
            ])
            ->nonPersist([
                $commit = new Commit([
                    'id' => 1,
                    'message' => 'Message de commit',
                    'project' => $project,
                    'authorId' => 1,
                    'authorType' => 'developer',
                ])
            ])
        ;

        Commit::repository()->relation('author')->loadIfNotLoaded(new SingleEntityIndexer(Commit::mapper(), $commit), ['project'], [], ['project.commits', 'project.developers']);

        $loadedAuthor = $commit->author;
        $commit->reload('author.project');

        $this->assertNotSame($loadedAuthor, $commit->author);
        $this->assertEntity($project, $commit->author->project);
    }
}
