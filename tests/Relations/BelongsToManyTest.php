<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\Collection\EntityCollection;
use Bdf\Prime\Collection\Indexer\EntityIndexer;
use Bdf\Prime\Collection\Indexer\SingleEntityIndexer;
use Bdf\Prime\Commit;
use Bdf\Prime\Company;
use Bdf\Prime\CustomerPack;
use Bdf\Prime\Developer;
use Bdf\Prime\FileUser;
use Bdf\Prime\Group;
use Bdf\Prime\Integrator;
use Bdf\Prime\Pack;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Customer;
use Bdf\Prime\Project;
use Bdf\Prime\ProjectIntegrator;
use Bdf\Prime\Test\RepositoryAssertion;
use Bdf\Prime\UserGroup;
use Doctrine\DBAL\Logging\DebugStack;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class BelongsToManyTest extends TestCase
{
    use PrimeTestCase;
    use RepositoryAssertion;

    /**
     * @var BelongsToMany
     */
    private $relation;

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
        $pack->persist([
            'customer' => new Customer([
                'id'            => '123',
                'name'          => 'Customer',
            ]),

            'pack-referencement' => new Pack([
                'id' => 1,
                'label' => 'Pack referencement',
            ]),
            'pack-classic' => new Pack([
                'id' => 2,
                'label' => 'Pack classic',
            ]),
            'pack-empty' => new Pack([
                'id' => 3,
                'label' => 'Pack empty',
            ]),
            'pack-empty2' => new Pack([
                'id' => 4,
                'label' => 'Pack empty2',
            ]),

            'customerPack-123-1' => new CustomerPack([
                'customerId' => '123',
                'packId' => 1,
            ]),
            'customerPack-123-2' => new CustomerPack([
                'customerId' => '123',
                'packId' => 2,
            ]),
        ]);

        $this->relation = Customer::repository()->relation('packs');
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
    public function test_load_relation()
    {
        $pack = $this->getTestPack()->get('pack-referencement');
        $pack2 = $this->getTestPack()->get('pack-classic');

        $customer = Prime::repository('Bdf\Prime\Customer')
            ->with('packs')
            ->get('123');

        $this->assertEquals([$pack, $pack2], $customer->packs);
        $this->assertTrue($this->relation->isLoaded($customer));
    }

    /**
     *
     */
    public function test_load_without_relation_should_not_execute_relation_query()
    {
        $customer = new Customer([
            'id'            => '124',
            'name'          => 'Customer2',
        ]);
        $this->getTestPack()->nonPersist($customer);

        $this->prime()->connection('test')->getConfiguration()->setSQLLogger($logger = new DebugStack());
        $customer->load('packs');

        $this->assertSame([], $customer->packs);
        $this->assertTrue($this->relation->isLoaded($customer));

        $this->assertCount(1, $logger->queries);
        $this->assertEquals('SELECT customer_id as customerId, pack_id as packId FROM customer_pack_ WHERE customer_id = ?', $logger->queries[1]['sql']);
        $this->assertEquals([1 => '124'], $logger->queries[1]['params']);
    }

    /**
     *
     */
    public function test_load_collection()
    {
        $this->getTestPack()->nonPersist([
            $withoutPack = new Customer([
                'id'            => '124',
                'name'          => 'Without pack',
            ]),
            $withPacks = new Customer([
                'id'            => '125',
                'name'          => 'Without pack',
            ]),
            new CustomerPack([
                'customerId' => '125',
                'packId' => 1,
            ]),
            new CustomerPack([
                'customerId' => '125',
                'packId' => 4,
            ]),
        ]);

        $customers = Customer::collection([
            $this->getTestPack()->get('customer'),
            $withoutPack,
            $withPacks,
        ]);
        $customers->load('packs');

        $this->assertTrue($this->relation->isLoaded($customers[0]));
        $this->assertTrue($this->relation->isLoaded($customers[1]));
        $this->assertTrue($this->relation->isLoaded($customers[2]));

        $this->assertEquals([
            $this->getTestPack()->get('pack-referencement'),
            $this->getTestPack()->get('pack-classic'),
        ], $customers[0]->packs);
        $this->assertSame([], $customers[1]->packs);
        $this->assertEquals([
            $this->getTestPack()->get('pack-referencement'),
            $this->getTestPack()->get('pack-empty2'),
        ], $customers[2]->packs);
        $this->assertSame($customers[0]->packs[0], $customers[2]->packs[0]);
    }

    /**
     *
     */
    public function test_load_with_constraints()
    {
        $customer = Prime::repository('Bdf\Prime\Customer')
            ->with(['packs' => ['id' => 2]])
            ->get('123');

        $this->assertEquals([$this->getTestPack()->get('pack-classic')], $customer->packs);
        $this->assertTrue($this->relation->isLoaded($customer));
    }

    /**
     *
     */
    public function test_load_with_pivot_constraints()
    {
        $customer = Prime::repository('Bdf\Prime\Customer')
            ->with(['packs' => ['packsThrough.packId' => 2]])
            ->get('123');

        $this->assertEquals([$this->getTestPack()->get('pack-classic')], $customer->packs);
        $this->assertTrue($this->relation->isLoaded($customer));
    }

    /**
     *
     */
    public function test_dynamic_join()
    {
        $customers = Prime::repository('Bdf\Prime\Customer')
            ->where('packs.label', ':like', '%classic')
            ->all();

        $this->assertEquals('123', $customers[0]->id);
    }

    /**
     *
     */
    public function test_load_single_entity()
    {
        $customer = $this->getTestPack()->get('customer');

        $this->assertFalse($this->relation->isLoaded($customer));

        $pack = $this->getTestPack()->get('pack-referencement');
        $pack2 = $this->getTestPack()->get('pack-classic');

        Prime::repository('Bdf\Prime\Customer')
            ->relation('packs')
                ->load(new SingleEntityIndexer(Customer::mapper(), $customer));

        $this->assertEquals([$pack, $pack2], $customer->packs);
        $this->assertTrue($this->relation->isLoaded($customer));
    }

    /**
     *
     */
    public function test_link_entity()
    {
        $customer = $this->getTestPack()->get('customer');

        $packs = Prime::repository('Bdf\Prime\Customer')
            ->relation('packs')
                ->link($customer)
                ->all();

        $this->assertEquals('1', $packs[0]->id);
        $this->assertEquals('2', $packs[1]->id);
    }

    /**
     *
     */
    public function test_associate()
    {
        $customer = $this->getTestPack()->get('customer');
        $pack = $this->getTestPack()->get('pack-empty');

        $count = $customer->relation('packs')->count();
        $customer->relation('packs')->associate($pack);

        $this->assertEquals($count + 1, $customer->relation('packs')->count());
        $this->assertFalse($this->relation->isLoaded($customer));
    }

    /**
     *
     */
    public function test_dissociate()
    {
        $customer = $this->getTestPack()->get('customer');
        $customer->packs[] = $this->getTestPack()->get('pack-referencement');

        $count = $customer->relation('packs')->count();
        $customer->relation('packs')->dissociate();

        $this->assertEquals($count - 1, $customer->relation('packs')->count());
        $this->assertFalse($this->relation->isLoaded($customer));
    }

    /**
     *
     */
    public function test_create()
    {
        $customer = $this->getTestPack()->get('customer');

        $pack = Prime::repository('Bdf\Prime\Customer')
            ->relation('packs')
            ->create($customer, ['id' => 20, 'label' => 'testPack']);

        $this->assertEquals(20, $pack->id);
        $this->assertEquals('testPack', $pack->label);
        $this->assertTrue(Prime::exists($pack));

        $expected = $customer->relation('packs')->get($pack->id);
        $this->assertEquals($expected, $pack);
        $this->assertFalse($this->relation->isLoaded($customer));
    }

    /**
     *
     */
    public function test_attach_detach_has()
    {
        $customer = $this->getTestPack()->get('customer');
        $pack = $this->getTestPack()->get('pack-empty');

        $relation = $customer->relation('packs');

        $this->assertFalse($relation->has($pack));
        $relation->attach($pack);
        $this->assertTrue($relation->has($pack));
        $relation->detach($pack);
        $this->assertFalse($relation->has($pack));
    }

    /**
     *
     */
    public function test_save_relation()
    {
        $customer = $this->getTestPack()->get('customer');

        $customer->packs[] = $this->getTestPack()->get('pack-empty');

        Prime::repository('Bdf\Prime\Customer')
            ->relation('packs')
            ->saveAll($customer);

        $nb = Prime::repository('Bdf\Prime\Customer')
            ->onRelation('packs', $customer)
            ->count();

        $this->assertEquals(1, $nb);
    }

    /**
     *
     */
    public function test_delete_relation()
    {
        $customer = $this->getTestPack()->get('customer');

        $customer->packs[] = $this->getTestPack()->get('pack-referencement');

        $affected = Prime::repository('Bdf\Prime\Customer')
            ->relation('packs')
                ->deleteAll($customer);

        $nb = Prime::repository('Bdf\Prime\Customer')
            ->onRelation('packs', $customer)
            ->count();

        $this->assertEquals(1, $nb);
        $this->assertEquals(1, $affected);
    }

    /**
     *
     */
    public function test_load_collection_without_subrelation()
    {
        $this->getTestPack()->declareEntity([
            Developer::class
        ]);

        $this->getTestPack()->nonPersist([
            'project' => new Project([
                'id' => 2,
                'name' => 'Projet 1'
            ]),
            'company' => new Company([
                'id' => 1,
                'name' => 'Société 1'
            ])
        ])
        ->nonPersist([
            new Integrator([
                'id' => 1,
                'name' => 'Intégrateur 1',
                'company' => $this->getTestPack()->get('company')
            ])
        ])
        ->nonPersist([
            new Commit([
                'id' => 1,
                'message' => 'Message de commit',
                'project' => $this->getTestPack()->get('project'),
                'authorId' => 1,
                'authorType' => 'integrator',
            ]),
            new ProjectIntegrator([
                'projectId' => 2,
                'integratorId' => 1
            ])
        ]);

        $repository = Prime::repository('Bdf\Prime\Integrator');

        $integrators = $repository->all();

        $relation = $repository->relation('projects');

        $this->assertFalse($relation->isLoaded($integrators[0]));

        $relation->load(EntityIndexer::fromArray(Integrator::mapper(), $integrators), [], [], ['commits']);

        $this->assertTrue($relation->isLoaded($integrators[0]));
        $this->assertEmpty($integrators[0]->projects[0]->commits);
    }

    /**
     *
     */
    public function test_load_without_subrelation()
    {
        $this->getTestPack()->declareEntity([
            Developer::class
        ]);

        $this->getTestPack()
            ->nonPersist([
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
                new Integrator([
                    'id' => 1,
                    'name' => 'Intégrateur 1',
                    'company' => $this->getTestPack()->get('company')
                ])
            ])
            ->nonPersist([
                new Commit([
                    'id' => 1,
                    'message' => 'Message de commit',
                    'project' => $this->getTestPack()->get('project'),
                    'authorId' => 1,
                    'authorType' => 'integrator',
                ]),
                new ProjectIntegrator([
                    'projectId' => 1,
                    'integratorId' => 1
                ])
            ]);

        $repository = Prime::repository('Bdf\Prime\Integrator');

        $integrator = $repository->get(1);

        $relation = $repository->relation('projects');
        $this->assertFalse($relation->isLoaded($integrator));

        $relation->load(new SingleEntityIndexer(Integrator::mapper(), $integrator), [], [], ['commits']);

        $this->assertEmpty($integrator->projects[0]->commits);
        $this->assertTrue($relation->isLoaded($integrator));
    }

    /**
     *
     */
    public function test_load_with_wrapper()
    {
        $this->getTestPack()->declareEntity([FileUser::class, Group::class, UserGroup::class]);

        $this->getTestPack()
            ->nonPersist([
                $user = new FileUser(['name' => 'my_user']),

                $group1 = new Group(['name' => 'group1']),
                $group2 = new Group(['name' => 'group2']),
                $group3 = new Group(['name' => 'group3']),

                new UserGroup(['userName' => 'my_user', 'groupName' => 'group1']),
                new UserGroup(['userName' => 'my_user', 'groupName' => 'group3'])
            ])
        ;

        $user->load('groups');

        $this->assertInstanceOf(EntityCollection::class, $user->groups);
        $this->assertSame(Group::repository(), $user->groups->repository());
        $this->assertEquals([$group1, $group3], $user->groups->all());
        $this->assertTrue(FileUser::repository()->relation('groups')->isLoaded($user));
    }

    /**
     *
     */
    public function test_deleteAll_with_wrapper()
    {
        $this->getTestPack()->declareEntity([FileUser::class, Group::class, UserGroup::class]);

        $this->getTestPack()
            ->nonPersist([
                $user = new FileUser(['name' => 'my_user']),

                $group1 = new Group(['name' => 'group1']),
                $group2 = new Group(['name' => 'group2']),
                $group3 = new Group(['name' => 'group3']),

                new UserGroup(['userName' => 'my_user', 'groupName' => 'group1']),
                new UserGroup(['userName' => 'my_user', 'groupName' => 'group3'])
            ])
        ;

        $user
            ->load('groups')
            ->relation('groups')->deleteAll()
        ;

        $this->assertEquals(0, UserGroup::count());
    }

    /**
     *
     */
    public function test_dissociate_with_wrapper()
    {
        $this->getTestPack()->declareEntity([FileUser::class, Group::class, UserGroup::class]);

        $this->getTestPack()
            ->nonPersist([
                $user = new FileUser(['name' => 'my_user']),

                $group1 = new Group(['name' => 'group1']),
                $group2 = new Group(['name' => 'group2']),
                $group3 = new Group(['name' => 'group3']),

                new UserGroup(['userName' => 'my_user', 'groupName' => 'group1']),
                new UserGroup(['userName' => 'my_user', 'groupName' => 'group3'])
            ])
        ;

        $user
            ->load('groups')
            ->relation('groups')->dissociate()
        ;

        $this->assertEquals(0, UserGroup::count());
    }

    /**
     *
     */
    public function test_saveAll_with_wrapper()
    {
        $this->getTestPack()->declareEntity([FileUser::class, Group::class, UserGroup::class]);

        $this->getTestPack()
            ->nonPersist([
                $user = new FileUser(['name' => 'my_user']),

                $group1 = new Group(['name' => 'group1']),
                $group2 = new Group(['name' => 'group2']),
                $group3 = new Group(['name' => 'group3']),

                new UserGroup(['userName' => 'my_user', 'groupName' => 'group1']),
                new UserGroup(['userName' => 'my_user', 'groupName' => 'group3'])
            ])
        ;

        $user->groups = Group::collection([$group1, $group2]);
        $user->relation('groups')->saveAll();

        $this->assertEquals([
            new UserGroup(['userName' => 'my_user', 'groupName' => 'group1']),
            new UserGroup(['userName' => 'my_user', 'groupName' => 'group2'])
        ], UserGroup::all());
    }

    /**
     *
     */
    public function test_load_twice_should_not_reload()
    {
        $this->getTestPack()
            ->nonPersist([
                $user = new FileUser(['name' => 'my_user']),

                $group1 = new Group(['name' => 'group1']),
                $group2 = new Group(['name' => 'group2']),
                $group3 = new Group(['name' => 'group3']),

                new UserGroup(['userName' => 'my_user', 'groupName' => 'group1']),
                new UserGroup(['userName' => 'my_user', 'groupName' => 'group3'])
            ])
        ;

        $this->assertFalse($user->relation('groups')->isLoaded());

        $user->load('groups');
        $this->assertTrue($user->relation('groups')->isLoaded());
        $loadedGroups = $user->groups;

        $user->load('groups');
        $this->assertSame($loadedGroups, $user->groups);
    }

    /**
     *
     */
    public function test_reload()
    {
        $this->getTestPack()
            ->nonPersist([
                $user = new FileUser(['name' => 'my_user']),

                $group1 = new Group(['name' => 'group1']),
                $group2 = new Group(['name' => 'group2']),
                $group3 = new Group(['name' => 'group3']),

                new UserGroup(['userName' => 'my_user', 'groupName' => 'group1']),
                new UserGroup(['userName' => 'my_user', 'groupName' => 'group3'])
            ])
        ;

        $user->load('groups');
        $loadedGroups = $user->groups;

        $user->reload('groups');
        $this->assertNotSame($loadedGroups, $user->groups);
    }
}
