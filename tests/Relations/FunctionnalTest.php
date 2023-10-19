<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\BarConfig;
use Bdf\Prime\BaseConfig;
use Bdf\Prime\Collection\EntityCollection;
use Bdf\Prime\Commit;
use Bdf\Prime\Company;
use Bdf\Prime\Developer;
use Bdf\Prime\Faction;
use Bdf\Prime\Folder;
use Bdf\Prime\FooConfig;
use Bdf\Prime\FooExtraConfig;
use Bdf\Prime\Integrator;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Customer;
use Bdf\Prime\Pack;
use Bdf\Prime\CustomerPack;
use Bdf\Prime\Project;
use Bdf\Prime\Test\TestPack;
use Bdf\Prime\TestFile;
use Bdf\Prime\User;
use Bdf\Prime\Test\RepositoryAssertion;
use Bdf\Prime\UserInheritedMetadata;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class FunctionnalTest extends TestCase
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
            ->declareEntity(Pack::class)
            ->declareEntity(Faction::class)
            ->declareEntity(User::class)
            ->declareEntity(Customer::class)
            ->declareEntity(CustomerPack::class)
            ->declareEntity(Project::class)
            ->declareEntity(Commit::class)
            ->declareEntity(Developer::class)
            ->declareEntity(Integrator::class)
            ->declareEntity(Company::class)
            ->declareEntity(Folder::class)
            ->declareEntity(TestFile::class)
        ;
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
    public function test_save_deep_cascade()
    {
        $user = new User([
            'id'            => 1,
            'name'          => 'Web User',
            'roles'         => [1],
            'customer'      => new Customer([
                'name'  => 'Customer',
                'packs' => [
                    new Pack([
                        'id'    => 1, 
                        'label' => 'Pack test'
                    ]),
                ],
            ]),
        ]);
        
        $user->customer->save();
        
        $user->saveAll('customer.packs');

        $this->assertEquals(1, Customer::count());
        $this->assertEquals(1, CustomerPack::count());
        $this->assertEquals(1, User::count());
    }
    
    /**
     *
     */
    public function test_save_cascade()
    {
        $user = new User([
            'id'            => 1,
            'name'          => 'Web User',
            'roles'         => [1],
            'customer'      => new Customer([
                'name'  => 'Customer',
                'packs' => [
                    new Pack([
                        'id'    => 1, 
                        'label' => 'Pack test'
                    ]),
                ],
            ]),
        ]);
        
        $user->customer->save();
        
        $user->saveAll('customer');

        $this->assertEquals(1, Customer::count());
        $this->assertEquals(0, CustomerPack::count());
        $this->assertEquals(1, User::count());
    }
    
    /**
     *
     */
    public function test_self_relation()
    {
        $customer = new Customer([
            'id'       => 1,
            'name'     => 'Customer',
            'parentId' => 2,
        ]);
        $parent = new Customer([
            'id'       => 2,
            'name'     => 'Parent',
        ]);
        
        $customer->insert();
        $parent->insert();
        
        $customer = Customer::with('parent')->get(1);
        
        $this->assertEquals('Parent', $customer->parent->name);
    }

    /**
     *
     */
    public function test_quote_identifier()
    {
        $query = UserInheritedMetadata::where('rights.name', 'name');

        $this->assertEquals('SELECT t0.* FROM webuser_ t0 INNER JOIN "rights_" "t1" ON "t1"."user_id" = "t0"."id_" WHERE "t1"."name_" = ?', $query->toSql());
    }

    /**
     *
     */
    public function test_self_join()
    {
        $customer = new Customer([
            'id'       => 1,
            'name'     => 'Customer',
            'parentId' => 2,
        ]);
        $parent = new Customer([
            'id'       => 2,
            'name'     => 'Parent',
        ]);

        $customer->insert();
        $parent->insert();

        $customer = Customer::where('parent.name', 'Parent')->first();

        $this->assertEquals(1, $customer->id);
    }

    /**
     *
     */
    public function test_relation_in_constraint()
    {
        $user = new User([
            'id'       => 1,
            'name'     => 'user',
            'roles'    => [1],
            'customer'  => new Customer([
                'id'       => 1,
                'name'     => 'customer',
            ]),
            'faction'  => new Faction([
                'id' => 1,
                'name' => 'orc',
                'domain' => 'user',
            ]),
        ]);
        $user->insert();
        $user->customer->insert();
        $user->faction->insert();

        $customer = Customer::where('webUsers.faction.name', 'orc')->first();

        $this->assertEquals(1, $customer->id);
    }

    /**
     *
     */
    public function test_eager_relation()
    {
        TestPack::pack()->nonPersist([
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
            'developer' => new Developer([
                'id' => 1,
                'name' => 'Dév 1',
                'project' => TestPack::pack()->get('project'),
                'company' => $this->getTestPack()->get('company')
            ])
        ]);

        $project = Project::where('id', 1)->first();

        $this->assertEquals(
            [
                new Developer([
                    'id' => 1,
                    'name' => 'Dév 1',
                    'project' => new Project(['id' => 1]),
                    'company' => $this->getTestPack()->get('company')
                ])
            ],
            $project->developers
        );
    }

    /**
     *
     */
    public function test_eager_relation_cascade()
    {
        TestPack::pack()->nonPersist([
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
            'developer' => new Developer([
                'id' => 1,
                'name' => 'Dév 1',
                'project' => TestPack::pack()->get('project'),
                'company' => $this->getTestPack()->get('company')
            ]),
            'commit' => new Commit([
                'id' => 1,
                'message' => 'Test message de commit',
                'authorId' => 1,
                'authorType' => 'developer',
                'project' => TestPack::pack()->get('project')]
            )
        ]);

        $project = Project::where('id', 1)->first();

        $this->assertEquals(
            new Developer([
                'id' => 1,
                'name' => 'Dév 1',
                'project' => new Project([
                    'id' => 1
                ]),
                'company' => $this->getTestPack()->get('company')
            ])
        , $project->commits[0]->author);
    }

    /**
     *
     */
    public function test_eager_relation_constraints()
    {
        TestPack::pack()->nonPersist([
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
            'developer' => new Developer([
                'id' => 1,
                'name' => 'Dév 1',
                'project' => TestPack::pack()->get('project'),
                'company' => $this->getTestPack()->get('company')
            ]),
            'leadDeveloper' => new Developer([
                'id' => 2,
                'name' => 'Dév 2',
                'lead' => true,
                'project' => TestPack::pack()->get('project'),
                'company' => $this->getTestPack()->get('company')
            ]),
        ]);

        $this->assertEntity(
            new Project([
                'id' => 1,
                'name' => 'Projet 1',
                'developers' => [
                    new Developer([
                        'id' => 1,
                        'name' => 'Dév 1',
                        'company' => $this->getTestPack()->get('company')
                    ]),
                    new Developer([
                        'id' => 2,
                        'name' => 'Dév 2',
                        'lead' => true,
                        'company' => $this->getTestPack()->get('company')
                    ])
                ],
                'leadDevelopers' => [
                    new Developer([
                        'id' => 2,
                        'name' => 'Dév 2',
                        'lead' => true,
                        'company' => $this->getTestPack()->get('company')
                    ])
                ]
            ]),
            Project::where('id', 1)->first()
        );
    }

    /**
     *
     */
    public function test_eager_relation_without()
    {
        TestPack::pack()->nonPersist([
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
            'developer' => new Developer([
                'id' => 1,
                'name' => 'Dév 1',
                'project' => TestPack::pack()->get('project'),
                'company' => $this->getTestPack()->get('company')
            ])
        ]);

        $project = Project::without('developers')->where('id', 1)->first();

        $this->assertNull($project->developers);
    }

    /**
     *
     */
    public function test_eager_relation_without_subrelation()
    {
        TestPack::pack()->nonPersist([
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
            'developer' => new Developer([
                'id' => 1,
                'name' => 'Dév 1',
                'project' => TestPack::pack()->get('project'),
                'company' => $this->getTestPack()->get('company')
            ]),
            'commit' => new Commit([
                    'id' => 1,
                    'message' => 'Test message de commit',
                    'authorId' => 1,
                    'authorType' => 'developer',
                    'project' => TestPack::pack()->get('project')]
            )
        ]);

        $project = Project::without('commits.author')->where('id', 1)->first();

        $this->assertNull($project->commits[0]->author);
    }

    /**
     *
     */
    public function test_eager_relation_without_sub_subrelation()
    {
        TestPack::pack()->nonPersist([
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
                'developer' => new Developer([
                    'id' => 1,
                    'name' => 'Dév 1',
                    'project' => TestPack::pack()->get('project'),
                    'company' => $this->getTestPack()->get('company')
                ]),
                'commit' => new Commit([
                        'id' => 1,
                        'message' => 'Test message de commit',
                        'authorId' => 1,
                        'authorType' => 'developer',
                        'project' => TestPack::pack()->get('project')]
                )
            ]);

        $project = Project::without('commits.author.company')->where('id', 1)->first();

        $this->assertEntity(new Company(['id' => 1]), $project->commits[0]->author->company);
    }

    /**
     *
     */
    public function test_wrapAs()
    {
        $folder = new Folder([
            'id' => 1,
            'name' => 'root'
        ]);

        $file1 = new TestFile([
            'id' => 1,
            'folderId' => 1,
            'name' => 'file1'
        ]);

        $file2 = new TestFile([
            'id' => 2,
            'folderId' => 1,
            'name' => 'file1'
        ]);

        $file3 = new TestFile([
            'id' => 3,
            'folderId' => 1,
            'name' => 'file1'
        ]);

        $folder->insert();
        $file1->insert();
        $file2->insert();
        $file3->insert();

        $folder->load('files');

        $this->assertInstanceOf(EntityCollection::class, $folder->files);
        $this->assertEntities([$file1, $file2, $file3], $folder->files->all());
    }

    public function test_inheritance_eager_loading()
    {
        $this->getTestPack()->nonPersist([
            $bar = new BarConfig([
                'id' => 42,
                'value' => 'bar',
            ]),
            $foo = new FooConfig([
                'id' => 43,
                'value' => 'foo',
            ]),
            $extra = new FooExtraConfig([
                'id' => 43,
                'foo' => 'extra',
            ]),
        ]);

        $entities = BaseConfig::all();

        $this->assertEntities([$bar, $foo], $entities);
        $this->assertNull($entities[0]->extra);
        $this->assertTrue($entities[0]->relation('extra')->isLoaded());
        $this->assertEntity($extra, $entities[1]->extra);
        $this->assertTrue($entities[1]->relation('extra')->isLoaded());
    }
}
