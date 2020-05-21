<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\Collection\EntityCollection;
use Bdf\Prime\Collection\Indexer\EntityIndexer;
use Bdf\Prime\Collection\Indexer\SingleEntityIndexer;
use Bdf\Prime\Commit;
use Bdf\Prime\Company;
use Bdf\Prime\Developer;
use Bdf\Prime\Folder;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Admin;
use Bdf\Prime\Customer;
use Bdf\Prime\Document;
use Bdf\Prime\Project;
use Bdf\Prime\TestFile;
use Bdf\Prime\User;
use Bdf\Prime\Test\RepositoryAssertion;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class HasManyTest extends TestCase
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

            'customer' => new Customer([
                'id'            => '123',
                'name'          => 'Customer',
            ]),
            'customer-empty' => new Customer([
                'id'            => '456',
                'name'          => 'Customer empty',
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
        $document = $this->getTestPack()->get('document-admin');
        $document2 = $this->getTestPack()->get('document-user');

        $customers = Prime::repository('Bdf\Prime\Customer')
            ->with('documents')
            ->all();

        $this->assertEquals([$document, $document2], $customers[0]->documents, 'documents on customer');
        $this->assertTrue($customers[0]->relation('documents')->isLoaded());
        $this->assertNull($customers[1]->documents, 'documents on customer 2');
        $this->assertFalse($customers[1]->relation('documents')->isLoaded());
    }

    /**
     *
     */
    public function test_load_collection_with_sub_relations()
    {
        $customer = Prime::repository('Bdf\Prime\Customer')
            ->with('documents.uploader')
            ->get('123');

        $this->assertEquals($this->getTestPack()->get('admin')->name, $customer->documents[0]->uploader->name);
    }

    /**
     *
     */
    public function test_load_collection_with_constraints()
    {
        $customer = Prime::repository('Bdf\Prime\Customer')
            ->with(['documents' => ['id' => 1]])
            ->get('123');

        $this->assertEquals([$this->getTestPack()->get('document-admin')], $customer->documents, 'documents on customer');
        $this->assertTrue($customer->relation('documents')->isLoaded());
    }
    
    /**
     *
     */
    public function test_dynamic_join()
    {
        $customers = Prime::repository('Bdf\Prime\Customer')
            ->where('documents.uploaderType', 'admin')
            ->all();

        $this->assertEquals('123', $customers[0]->id);
    }

    /**
     *
     */
    public function test_load_single_entity()
    {
        $customer = $this->getTestPack()->get('customer');

        $document = $this->getTestPack()->get('document-admin');
        $document2 = $this->getTestPack()->get('document-user');

        Prime::repository('Bdf\Prime\Customer')->relation('documents')
            ->load(new SingleEntityIndexer(Customer::mapper(), $customer));

        $this->assertEquals([$document, $document2], $customer->documents, 'documents on customer');
        $this->assertTrue($customer->relation('documents')->isLoaded());
    }

    /**
     *
     */
    public function test_link_entity()
    {
        $customer = $this->getTestPack()->get('customer');

        $documents = Prime::repository('Bdf\Prime\Customer')->relation('documents')
            ->link($customer)
            ->all();

        $this->assertEquals('1', $documents[0]->id);
        $this->assertEquals('2', $documents[1]->id);
    }

    /**
     *
     */
    public function test_associate_dont_associate()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The local entity is not the foreign key barrier.');

        $customer = $this->getTestPack()->get('customer');
        $document = new Document();

        $customer->relation('documents')->associate($document);
    }

    /**
     *
     */
    public function test_dissociate_dont_dissociate()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The local entity is not the foreign key barrier.');

        $customer = $this->getTestPack()->get('customer');

        $customer->relation('documents')->dissociate();
    }

    /**
     *
     */
    public function test_create()
    {
        $customer = $this->getTestPack()->get('customer');

        $document = Prime::repository('Bdf\Prime\Customer')
            ->relation('documents')
            ->create($customer, ['uploaderType' => 3]);

        $this->assertEquals($customer->id, $document->customerId);
        $this->assertEquals(3, $document->uploaderType);
        $this->assertFalse($customer->relation('documents')->isLoaded());
    }

    /**
     *
     */
    public function test_save_relation()
    {
        $customer = $this->getTestPack()->get('customer');
        $customer->documents[] = new Document([
            'uploaderType'   => 'user',
            'uploaderId'     => '321'
        ]);

        $affected = Prime::repository('Bdf\Prime\Customer')
            ->relation('documents')
            ->saveAll($customer);

        $nb = Prime::repository('Bdf\Prime\Customer')
            ->onRelation('documents', $customer)
            ->count();

        $this->assertEquals(1, $affected);
        $this->assertEquals(1, $nb);
    }

    /**
     *
     */
    public function test_delete_relation()
    {
        $customer = Prime::repository('Bdf\Prime\Customer')->with('documents')->get('123');

        $affected = Prime::repository('Bdf\Prime\Customer')
            ->relation('documents')
            ->deleteAll($customer);

        $nb = Prime::repository('Bdf\Prime\Customer')
            ->onRelation('documents', $customer)
            ->count();

        $this->assertEquals(2, $affected);
        $this->assertEquals(0, $nb);
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

        $repository = Prime::repository('Bdf\Prime\Developer');

        $developers = $repository->all();

        $relation = $repository->relation('commits');
        $relation->load(EntityIndexer::fromArray(Developer::mapper(), $developers), [], [], ['author']);

        $this->assertNull($developers[0]->commits[0]->author);
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

        $repository = Prime::repository('Bdf\Prime\Developer');

        $developer = $repository->get(1);

        $relation = $repository->relation('commits');
        $relation->load(new SingleEntityIndexer(Developer::mapper(), $developer), [], [], ['author']);

        $this->assertNull($developer->commits[0]->author);
    }

    /**
     *
     */
    public function test_load_with_wrapper()
    {
        $this->getTestPack()->declareEntity([Folder::class, TestFile::class]);

        $this->getTestPack()
            ->nonPersist([
                $folder = new Folder(['id' => 123, 'name' => 'My folder']),
                $file1 = new TestFile(['id' => 1, 'name' => 'file1', 'folderId' => 123]),
                $file2 = new TestFile(['id' => 2, 'name' => 'file2', 'folderId' => 123]),
                $file3 = new TestFile(['id' => 3, 'name' => 'file3', 'folderId' => 123]),
            ])
        ;

        $this->assertFalse($folder->relation('files')->isLoaded());
        $folder->load('files');

        $this->assertInstanceOf(EntityCollection::class, $folder->files);
        $this->assertSame(TestFile::repository(), $folder->files->repository());
        $this->assertEquals([$file1, $file2, $file3], $folder->files->all());
        $this->assertTrue($folder->relation('files')->isLoaded());
    }

    /**
     *
     */
    public function test_deleteAll_with_wrapper()
    {
        $this->getTestPack()->declareEntity([Folder::class, TestFile::class]);

        $this->getTestPack()
            ->nonPersist([
                $folder = new Folder(['id' => 123, 'name' => 'My folder']),
                $file1 = new TestFile(['id' => 1, 'name' => 'file1', 'folderId' => 123]),
                $file2 = new TestFile(['id' => 2, 'name' => 'file2', 'folderId' => 123]),
                $file3 = new TestFile(['id' => 3, 'name' => 'file3', 'folderId' => 123]),
            ])
        ;

        $folder->load('files');
        $folder->relation('files')->deleteAll();

        $this->assertEquals(0, TestFile::count());
        //$this->assertCount(0, $folder->load('files')->files); // @todo #15874
    }

    /**
     *
     */
    public function test_saveAll_with_wrapper()
    {
        $this->getTestPack()->declareEntity([Folder::class, TestFile::class]);

        $this->getTestPack()
            ->nonPersist([
                $folder = new Folder(['id' => 123, 'name' => 'My folder']),
            ])
        ;

        $folder->files = TestFile::collection([
            $file1 = new TestFile(['name' => 'file1']),
            $file2 = new TestFile(['name' => 'file2']),
            $file3 = new TestFile(['name' => 'file3']),
        ]);

        $folder->relation('files')->saveAll();
        $this->assertEquals([$file1, $file2, $file3], TestFile::where(['folderId' => 123])->all());
    }

    /**
     *
     */
    public function test_load_twice_should_not_reload()
    {
        $customer = $this->getTestPack()->get('customer');

        $customer->load('documents');
        $this->assertTrue($customer->relation('documents')->isLoaded());
        $loadedDocuments = $customer->documents;
        $this->assertCount(2, $loadedDocuments);
        $this->assertFalse($customer->documents[0]->relation('uploader')->isLoaded());
        $this->assertFalse($customer->documents[1]->relation('uploader')->isLoaded());

        $customer->load('documents.uploader');
        $this->assertSame($loadedDocuments, $customer->documents);
        $this->assertTrue($customer->documents[0]->relation('uploader')->isLoaded());
        $this->assertTrue($customer->documents[1]->relation('uploader')->isLoaded());
    }

    /**
     *
     */
    public function test_reload()
    {
        $customer = $this->getTestPack()->get('customer');

        $customer->load('documents');
        $loadedDocuments = $customer->documents;

        $customer->reload('documents');
        $this->assertNotSame($loadedDocuments, $customer->documents);
    }
}
