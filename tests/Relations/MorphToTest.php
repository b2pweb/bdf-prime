<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\Collection\Indexer\EntityIndexer;
use Bdf\Prime\Collection\Indexer\SingleEntityIndexer;
use Bdf\Prime\Commit;
use Bdf\Prime\Company;
use Bdf\Prime\Developer;
use Bdf\Prime\Integrator;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Project;
use Bdf\Prime\Test\RepositoryAssertion;
use Bdf\Prime\Test\TestPack;
use Bdf\Prime\Admin;
use Bdf\Prime\Customer;
use Bdf\Prime\Document;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class MorphToTest extends TestCase
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
        $pack->persist([
            'admin' => new Admin([
                'id'            => '10',
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

            'customer' => new Customer([
                'id'            => '123',
                'name'          => 'Customer',
            ]),

            'document-admin' => new Document([
                'id'             => '10',
                'customerId'     => '123',
                'uploaderType'   => 'admin',
                'uploaderId'     => '10',
            ]),

            'document-user' => new Document([
                'id'             => '20',
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
    public function test_one_morph()
    {
        $admin = TestPack::pack()->get('admin');
        
        $document = Prime::repository('Bdf\Prime\Document')->with('uploader')->get(10);

        $this->assertEquals(get_class($admin), get_class($document->uploader));
        $this->assertEquals($admin->name, $document->uploader->name);
        $this->assertTrue($document->relation('uploader')->isLoaded());
    }

    /**
     * 
     */
    public function test_all_morph()
    {
        $admin = TestPack::pack()->get('admin');
        $user = TestPack::pack()->get('user');
        
        $documents = Document::with('uploader')->all();

        $this->assertEquals(get_class($admin), get_class($documents[0]->uploader));
        $this->assertEquals($admin->name, $documents[0]->uploader->name);
        $this->assertTrue($documents[0]->relation('uploader')->isLoaded());
        $this->assertEquals(get_class($user), get_class($documents[1]->uploader));
        $this->assertEquals($user->name, $documents[1]->uploader->name);
        $this->assertTrue($documents[1]->relation('uploader')->isLoaded());
    }
    
    /**
     * 
     */
    public function test_other_morph()
    {
        $user = TestPack::pack()->get('user');
        
        $document = Document::with('uploader')->get(20);
        
        $this->assertEquals(get_class($user), get_class($document->uploader));
        $this->assertEquals($user->name, $document->uploader->name);
        $this->assertTrue($document->relation('uploader')->isLoaded());
    }
    
    /**
     *
     */
    public function test_morph_with_sub_relation()
    {
        $user = TestPack::pack()->get('user');

        $document = Document::with('uploader#user.customer')->get(20);

        $this->assertEquals($user->customer->name, $document->uploader->customer->name);
    }

    /**
     *
     */
    public function test_load_single_entity()
    {
        $document = TestPack::pack()->get('document-user');
        $user = TestPack::pack()->get('user');

        $this->assertFalse($document->relation('uploader')->isLoaded());
        $document->load('uploader');

        $this->assertEquals($user->name, $document->uploader->name);
        $this->assertTrue($document->relation('uploader')->isLoaded());
    }

    /**
     *
     */
    public function test_load_sub_relation_non_concerned()
    {
        $admin = TestPack::pack()->get('admin');
        $document = TestPack::pack()->get('document-admin');

        $document->load('uploader#user.customer');

        $this->assertEquals($admin->name, $document->uploader->name);
    }

    /**
     *
     */
    public function test_load_sub_relation()
    {
        $customer = TestPack::pack()->get('customer');
        $document = TestPack::pack()->get('document-user');

        $document->load([
            'uploader#user.customer',
            'uploader#admin.faction',
        ]);

        $this->assertEquals($customer->name, $document->uploader->customer->name);
    }

    /**
     *
     */
    public function test_load_sub_relation_without_discriminator()
    {
        $customer = TestPack::pack()->get('customer');
        $document = TestPack::pack()->get('document-user');

        $document->load('uploader.customer');

        $this->assertEquals($customer->name, $document->uploader->customer->name);
    }

    /**
     *
     */
    public function test_load_sub_relation_without_discriminator_non_concerned()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessageMatches('/Relation "customer" is not set/');

        $admin = TestPack::pack()->get('admin');
        $document = TestPack::pack()->get('document-admin');

        $document->load('uploader.customer');
    }

    /**
     *
     */
    public function test_join_needs_discriminator()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Joins are not supported on polymorph without discriminator');

        $relation = Prime::repository('Bdf\Prime\Document')->relation('uploader');
        $relation->join(Prime::repository('Bdf\Prime\Document')->builder(), 'uploader');
    }

    /**
     *
     */
    public function test_dynamic_join()
    {
        $document = Prime::repository('Bdf\Prime\Document')
            ->where('uploader#user.name', 'Web User')
            ->first();

        $this->assertEquals('20', $document->id);
    }

    /**
     *
     */
    public function test_far_dynamic_join()
    {
        $document = Prime::repository('Bdf\Prime\Document')
            ->where('uploader#user.customer.name', 'Customer')
            ->first();

        $this->assertEquals('20', $document->id);
    }

    /**
     *
     */
    public function test_link_entity()
    {
        $document = TestPack::pack()->get('document-user');

        $user = Prime::repository('Bdf\Prime\Document')
            ->relation('uploader')
            ->link($document)
            ->first();

        $this->assertEquals(TestPack::pack()->get('user')->name, $user->name);
    }

    /**
     *
     */
    public function test_associate()
    {
        $document = TestPack::pack()->get('document-user');

        $user = new Admin([
            'id'   => 100,
            'name' => 'User associated'
        ]);

        $document->relation('uploader')->associate($user);

        $this->assertEquals($user, $document->uploader);
        $this->assertEquals($user->id, $document->uploaderId);
        $this->assertEquals('admin', $document->uploaderType);
        $this->assertTrue($document->relation('uploader')->isLoaded());
    }

    /**
     *
     */
    public function test_dissociate()
    {
        $document = TestPack::pack()->get('document-user');
        $document->relation('uploader')->dissociate();

        $this->assertEquals(null, $document->uploader);
        $this->assertEquals(null, $document->uploaderId);
        $this->assertEquals(null, $document->uploaderType);
        $this->assertFalse($document->relation('uploader')->isLoaded());
    }

    /**
     *
     */
    public function test_relation_method_should_define_distant_repository()
    {
        /** @var Document $document */
        $document = TestPack::pack()->get('document-user');

        $user = new Admin([
            'id'   => 100,
            'name' => 'User associated'
        ]);
        /** @var MorphTo $relation */
        $relation = Document::repository()->relation('uploader');
        $resetRelation = \Closure::bind(function () use($relation) {
            $relation->discriminatorValue = null;
            $relation->distant = null;
            $relation->distantKey = null;
        }, null, MorphTo::class);

        $resetRelation();
        $document->relation('uploader')->associate($user);
        $this->assertEquals(Admin::repository(), $relation->relationRepository());

        $document->uploader = $user;
        $document->uploaderType = 'admin';

        $resetRelation();
        $document->relation('uploader')->query();
        $this->assertEquals(Admin::repository(), $relation->relationRepository());
    }

    /**
     *
     */
    public function test_create()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The local entity is not the primary key barrier.');

        $document = TestPack::pack()->get('document-user');

        Prime::repository('Bdf\Prime\Document')
            ->relation('uploader')
            ->create($document, ['name' => 'CreateUser']);
    }

    /**
     *
     */
    public function test_save_relation()
    {
        $document = TestPack::pack()->get('document-admin');
        $document->uploaderType = 'admin';
        $document->uploader = new Admin([
            'name' => 'relation',
            'roles' => [1],
        ]);

        $affected = $document->relation('uploader')->saveAll();

        $nb = Admin::where('name', 'relation')->count();

        $this->assertEquals(1, $nb);
        $this->assertEquals(1, $affected);
    }

    /**
     *
     */
    public function test_save_all()
    {
        $document = Document::with('uploader.customer')->get(20);
        $document->uploader->customer->name = 'save all';

        $document->saveAll('uploader.customer');

        $customer = Customer::get($document->uploader->customer->id);

        $this->assertEquals('save all', $customer->name);
    }

    /**
     *
     */
    public function test_save_all_with_discriminator()
    {
        $document = Document::with('uploader.customer')->get(20);
        $document->uploader->customer->name = 'save all';

        $document->saveAll([
            'uploader#user.customer',
            'uploader#admin.faction',
        ]);

        $customer = Customer::get($document->uploader->customer->id);

        $this->assertEquals('save all', $customer->name);
    }

    /**
     *
     */
    public function test_delete_relation()
    {
        $document = TestPack::pack()->get('document-user');
        $document->load('uploader');

        $affected = $document->relation('uploader')->deleteAll();

        $uploader = User::get($document->uploader->id);

        $this->assertEquals(1, $affected);
        $this->assertEquals(null, $uploader);
    }

    /**
     *
     */
    public function test_delete_all()
    {
        $document = Document::with('uploader.customer')->get(20);
        $document->deleteAll('uploader.customer');

        $this->assertNull(Customer::get($document->uploader->customer->id));
        $this->assertNull(User::get($document->uploader->id));
        $this->assertNull(Document::get($document->id));
    }

    /**
     *
     */
    public function test_delete_all_with_discriminator()
    {
        $document = Document::with('uploader.customer')->get(20);
        $document->deleteAll([
            'uploader#user.customer',
            'uploader#admin.faction',
        ]);

        $this->assertNull(Customer::get($document->uploader->customer->id));
        $this->assertNull(User::get($document->uploader->id));
        $this->assertNull(Document::get($document->id));
    }

    /**
     *
     */
    public function test_load_collection_without_subrelation()
    {
        $this->getTestPack()
            ->declareEntity(Developer::class)
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
                'author' => new Integrator([
                    'id' => 1,
                    'name' => 'Dév 1',
                    'company' => $this->getTestPack()->get('company')
                ])
            ])
            ->nonPersist([
                'commit' => new Commit([
                    'id' => 1,
                    'message' => 'Message de commit',
                    'project' => $this->getTestPack()->get('project'),
                    'authorId' => 1,
                    'authorType' => 'integrator',
                ])
            ]);

        $repository = Prime::repository('Bdf\Prime\Commit');

        $commits = $repository->without('author')->all();

        $relation = $repository->relation('author');
        $relation->load(EntityIndexer::fromArray($repository->mapper(), $commits), [], [], ['company']);

        $this->assertEmpty($commits[0]->author->company->name);
    }

    /**
     *
     */
    public function test_load_without_subrelation()
    {
        $this->getTestPack()
            ->declareEntity(Developer::class)
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
                'author' => new Integrator([
                    'id' => 1,
                    'name' => 'Dév 1',
                    'company' => $this->getTestPack()->get('company')
                ])
            ])
            ->nonPersist([
                'commit' => new Commit([
                    'id' => 1,
                    'message' => 'Message de commit',
                    'project' => $this->getTestPack()->get('project'),
                    'authorId' => 1,
                    'authorType' => 'integrator',
                ])
            ]);

        $repository = Prime::repository('Bdf\Prime\Commit');

        $commit = $repository->without('author')->get(1);

        $relation = $repository->relation('author');
        $relation->load(new SingleEntityIndexer(Commit::mapper(), $commit), [], [], ['company']);

        $this->assertEmpty($commit->author->company->name);
        $this->assertTrue($commit->relation('author')->isLoaded());
        $this->assertFalse($commit->author->relation('company')->isLoaded());
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
        ])
            ->nonPersist([
                'commit' => new Commit([
                    'id' => 1,
                    'message' => 'Test message de commit',
                    'authorId' => 1,
                    'authorType' => 'integrator',
                    'project' => TestPack::pack()->get('project')
                ]),
                'company' => new Company([
                    'id' => 1,
                    'name' => 'Capsule Corp.'
                ]),
            ])
            ->nonPersist([
                'integrator' => new Integrator([
                    'id' => 1,
                    'name' => 'Intégrateur 1',
                    'company' => TestPack::pack()->get('company')
                ])
            ]);

        $commit = Commit::without('author#integrator.company')->where('id', 1)->first();

        $this->assertEmpty($commit->author->company->name);
        $this->assertTrue($commit->relation('author')->isLoaded());
        $this->assertFalse($commit->author->relation('company')->isLoaded());
    }

    /**
     *
     */
    public function test_load_twice_should_not_reload()
    {
        $document = TestPack::pack()->get('document-user');

        $document->load('uploader');
        $this->assertTrue($document->relation('uploader')->isLoaded());
        $this->assertFalse($document->uploader->relation('customer')->isLoaded());
        $loadedUploader = $document->uploader;

        $document->load('uploader.customer');
        $this->assertSame($loadedUploader, $document->uploader);
        $this->assertTrue($document->uploader->relation('customer')->isLoaded());
    }

    /**
     *
     */
    public function test_reload()
    {
        $document = TestPack::pack()->get('document-user');

        $document->load('uploader');
        $loadedUploader = $document->uploader;

        $document->reload('uploader.customer');
        $this->assertNotSame($loadedUploader, $document->uploader);
    }
}
