<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\Collection\Indexer\EntityIndexer;
use Bdf\Prime\Collection\Indexer\SingleEntityIndexer;
use Bdf\Prime\Commit;
use Bdf\Prime\Company;
use Bdf\Prime\Developer;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Customer;
use Bdf\Prime\Location;
use Bdf\Prime\Project;
use Bdf\Prime\Test\RepositoryAssertion;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class HasOneTest extends TestCase
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
            ->persist([
                'customer' => new Customer([
                    'id'            => '123',
                    'name'          => 'Customer 123',
                ]),
                'customer-nolocation' => new Customer([
                    'id'            => '321',
                    'name'          => 'Customer 321',
                ]),
                'location' => new Location([
                    'id'      => '123',
                    'address' => '1 rue chez toi',
                    'city'    => 'MAISON',
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
        $customers = Customer::with('location')->by('id')->all();

        $this->assertEquals('123', $customers['123']->location->id);
        $this->assertTrue($customers['123']->relation('location')->isLoaded());
        $this->assertEquals(null, $customers['321']->location);
        $this->assertFalse($customers['321']->relation('location')->isLoaded());
    }

    /**
     *
     */
    public function test_load_collection_with_constraints()
    {
        $customers = Customer::with(['location' => ['city' => 'nowhere']])->by('id')->all();

        $this->assertEquals(null, $customers['123']->location);
        $this->assertFalse($customers['123']->relation('location')->isLoaded());
        $this->assertEquals(null, $customers['321']->location);
        $this->assertFalse($customers['321']->relation('location')->isLoaded());
    }
    
    /**
     *
     */
    public function test_dynamic_join()
    {
        $customer = $this->getTestPack()->get('customer');
        $customers = Customer::where('location.city', 'MAISON')->all();

        $this->assertEquals(1, count($customers));
        $this->assertEquals($customer->id, $customers[0]->id);
    }

    /**
     *
     */
    public function test_load_single_entity()
    {
        $customer = $this->getTestPack()->get('customer');

        $this->assertEquals(null, $customer->location);

        $this->assertFalse($customer->relation('location')->isLoaded());
        $customer->load('location');

        $this->assertEquals($customer->id, $customer->location->id);
        $this->assertTrue($customer->relation('location')->isLoaded());
    }

    /**
     *
     */
    public function test_link_entity()
    {
        $customer = $this->getTestPack()->get('customer');

        $location = $customer->relation('location')->first();

        $this->assertEquals($customer->id, $location->id);
    }

    /**
     *
     */
    public function test_associate_dont_associate()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The local entity is not the foreign key barrier.');

        $customer = $this->getTestPack()->get('customer-nolocation');
        $location = new Location(['city' => 'home']);

        $customer->relation('location')->associate($location);
    }

    /**
     *
     */
    public function test_dissociate_dont_dissociate()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The local entity is not the foreign key barrier.');

        $customer = $this->getTestPack()->get('customer');

        $customer->relation('location')->dissociate();
    }

    /**
     *
     */
    public function test_create()
    {
        $customer = $this->getTestPack()->get('customer-nolocation');

        $location = $customer->relation('location')->create(['city' => 'home']);

        $this->assertEquals($customer->id, $location->id);
        $this->assertEquals('home', $location->city);
    }

    /**
     *
     */
    public function test_save_relation()
    {
        $customer = $this->getTestPack()->get('customer-nolocation');
        $customer->location = new Location([
            'address' => '123 street',
            'city'    => 'home',
        ]);

        $affected = $customer->relation('location')->saveAll();

        $nb = Location::where('city', 'home')->count();

        $this->assertEquals($customer->id, $customer->location->id);
        $this->assertEquals(1, $nb);
        $this->assertEquals(1, $affected);
    }

    /**
     *
     */
    public function test_save_empty_relation()
    {
        $count = Location::count();

        $customer = $this->getTestPack()->get('customer');
        $customer->location = null;

        $affected = $customer->relation('location')->saveAll();

        $this->assertEquals(0, $affected);
        $this->assertEquals($count, Location::count());
    }

    /**
     *
     */
    public function test_delete_relation()
    {
        $customer = $this->getTestPack()->get('customer');
        $customer->load('location');

        $affected = $customer->relation('location')->deleteAll();

        $location = Location::get($customer->location->id);

        $this->assertEquals(1, $affected);
        $this->assertEquals(null, $location);
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

        $repository = Prime::repository('Bdf\Prime\Project');

        $projects = $repository->without('creator')->all();

        $relation = $repository->relation('creator');
        $relation->load(EntityIndexer::fromArray(Developer::mapper(), $projects), [], [], ['company']);

        $this->assertEntity(new Company(['id' => 1]), $projects[0]->creator->company);
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

        $repository = Prime::repository('Bdf\Prime\Project');

        $project = $repository->without('creator')->get(1);

        $relation = $repository->relation('creator');
        $relation->load(new SingleEntityIndexer(Project::mapper(), $project), [], [], ['company']);

        $this->assertEntity(new Company(['id' => 1]), $project->creator->company);
        $this->assertTrue($project->relation('creator')->isLoaded());
        $this->assertFalse($project->creator->relation('company')->isLoaded());
    }

    /**
     *
     */
    public function test_load_self_relation_chain()
    {
        $this->getTestPack()
            ->nonPersist([
                $child = new Customer([
                    'id'       => '1',
                    'name'     => 'child',
                    'parentId' => '2',
                ]),
                $parent = new Customer([
                    'id'       => '2',
                    'name'     => 'parent',
                    'parentId' => '3',
                ]),
                $grandParent = new Customer([
                    'id'   => '3',
                    'name' => 'grand-parent',
                ]),
            ])
        ;

        $child->load('parent.parent');

        $this->assertEntity($parent, $child->parent);
        $this->assertEntity($grandParent, $child->parent->parent);
    }

    /**
     *
     */
    public function test_load_twice_should_not_reload()
    {
        $customer = $this->getTestPack()->get('customer');

        $customer->load('location');
        $this->assertTrue($customer->relation('location')->isLoaded());
        $loadedLocation = $customer->location;

        $customer->load('location');
        $this->assertSame($loadedLocation, $customer->location);
    }

    /**
     *
     */
    public function test_reload()
    {
        $customer = $this->getTestPack()->get('customer');

        $customer->load('location');
        $loadedLocation = $customer->location;

        $customer->reload('location');
        $this->assertNotSame($loadedLocation, $customer->location);
    }
}
