<?php

namespace Php74\Relations;

require_once __DIR__.'/../_files/relation.php';

use Bdf\Prime\Collection\Indexer\EntityIndexer;
use Bdf\Prime\Collection\Indexer\SingleEntityIndexer;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Relations\BelongsToMany;
use Bdf\Prime\Test\RepositoryAssertion;
use Php74\Commit;
use Php74\Company;
use Php74\Customer;
use Php74\CustomerPack;
use Php74\Developer;
use Php74\Integrator;
use Php74\Pack;
use Php74\Project;
use Php74\ProjectIntegrator;
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
        $this->unsetPrime();
    }

    /**
     *
     */
    public function test_load_relation()
    {
        $pack = $this->getTestPack()->get('pack-referencement');
        $pack2 = $this->getTestPack()->get('pack-classic');

        $customer = Prime::repository(Customer::class)
            ->with('packs')
            ->get('123');

        $this->assertEquals([$pack, $pack2], $customer->packs);
        $this->assertTrue($this->relation->isLoaded($customer));
    }

    /**
     *
     */
    public function test_load_with_constraints()
    {
        $customer = Prime::repository(Customer::class)
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
        $customer = Prime::repository(Customer::class)
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
        $customers = Prime::repository(Customer::class)
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

        Prime::repository(Customer::class)
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

        $packs = Prime::repository(Customer::class)
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

        $pack = Prime::repository(Customer::class)
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

        Prime::repository(Customer::class)
            ->relation('packs')
            ->saveAll($customer);

        $nb = Prime::repository(Customer::class)
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

        $affected = Prime::repository(Customer::class)
            ->relation('packs')
                ->deleteAll($customer);

        $nb = Prime::repository(Customer::class)
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

        $repository = Prime::repository(Integrator::class);

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

        $repository = Prime::repository(Integrator::class);

        $integrator = $repository->get(1);

        $relation = $repository->relation('projects');
        $this->assertFalse($relation->isLoaded($integrator));

        $relation->load(new SingleEntityIndexer(Integrator::mapper(), $integrator), [], [], ['commits']);

        $this->assertEmpty($integrator->projects[0]->commits);
        $this->assertTrue($relation->isLoaded($integrator));
    }
}
