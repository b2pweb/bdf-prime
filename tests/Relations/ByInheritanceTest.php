<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\Collection\Indexer\EntityIndexer;
use Bdf\Prime\Collection\Indexer\SingleEntityIndexer;
use Bdf\Prime\Customer;
use Bdf\Prime\CustomerControlTask;
use Bdf\Prime\Document;
use Bdf\Prime\DocumentControlTask;
use Bdf\Prime\DocumentEager;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Task;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ByInheritanceTest extends TestCase
{
    use PrimeTestCase;

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
        $pack->declareEntity([DocumentEager::class])->persist([
            'documentTask' => new DocumentControlTask([
                'id'            => '10',
                'name'          => 'document',
                'targetId'      => '20',
            ]),
            'documentTaskEager' => new DocumentControlTask([
                'id'            => '100',
                'name'          => 'document',
                'targetId'      => '200',
            ]),
            'customerTask' => new CustomerControlTask([
                'id'            => '11',
                'name'          => 'customer',
                'targetId'      => '21',
            ]),
            'document' => new Document([
                'id'          => '20',
                'customerId'  => '21',
                'uploaderType'=> 'user',
                'uploaderId'  => '30',
            ]),
            'documentEager' => new DocumentEager([
                'id'          => '200',
                'customerId'  => '21',
                'uploaderType'=> 'user',
                'uploaderId'  => '30',
            ]),
            'customer' => new Customer([
                'id'            => '21',
                'name'          => 'John ind.',
            ]),
            'user' => new User([
                'id'            => '30',
                'name'          => 'John Doe',
                'roles'         => [],
                'customer'      => new Customer(['id' => 21])
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
    public function test_inheritance_depends_of_inheritance_mapper()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('The mapper could not manage single table inheritance relation');

        (new ByInheritance('test', Prime::repository('Bdf\Prime\User'), 'id'));
    }

    /**
     *
     */
    public function test_relation_from_parent()
    {
        $tasks = Task::with('target')->all();

        $this->assertEquals('document', $tasks[0]->name);
        $this->assertEquals('21', $tasks[0]->target->customerId);
        $this->assertEquals('customer', $tasks[1]->name);
        $this->assertEquals('John ind.', $tasks[1]->target->name);
    }

    /**
     *
     */
    public function test_child_relation()
    {
        $task = CustomerControlTask::with('target')->get(11);

        $this->assertEquals('John ind.', $task->target->name);
    }

    /**
     *
     */
    public function test_with_sub_relation()
    {
        $user = $this->pack()->get('user');
        $task = Task::with('target#DocumentControl.uploader')->get(10);

        $this->assertEquals($user->name, $task->target->uploader->name);
    }

    /**
     *
     */
    public function test_with_complex_sub_relation()
    {
        $customer = $this->pack()->get('customer');
        $task = Task::with('target#DocumentControl.uploader#user.customer')->get(10);

        $this->assertEquals($customer->name, $task->target->uploader->customer->name);
    }

    /**
     *
     */
    public function test_load_sub_relation_non_concerned()
    {
        $task = $this->pack()->get('customerTask');
        $customer = $this->pack()->get('customer');

        Task::loadRelations($task, 'target#DocumentControl.uploader');

        $this->assertEquals($customer->name, $task->target->name);
    }

    /**
     *
     */
    public function test_load_sub_relation()
    {
        $user = $this->pack()->get('user');
        $task = $this->pack()->get('documentTask');

        Task::loadRelations($task, [
            'target#DocumentControl.uploader',
            'target#CustomerControl.packs',
        ]);

        $this->assertEquals($user->name, $task->target->uploader->name);
    }

    /**
     *
     */
    public function test_load_complex_sub_relation()
    {
        $customer = $this->pack()->get('customer');
        $task = $this->pack()->get('documentTask');

        Task::loadRelations($task, [
            'target#DocumentControl.uploader#user.customer',
            'target#CustomerControl.packs',
        ]);

        $this->assertEquals($customer->name, $task->target->uploader->customer->name);
    }

    /**
     *
     */
    public function test_dynamic_join()
    {
        $task = Task::where('target#CustomerControl.name', 'John ind.')->first();

        $this->assertEquals('11', $task->id);
    }

    /**
     *
     */
    public function test_dynamic_join_with_root_conditional()
    {
        $task = Task::where([
            'name' => 'customer',
            'target#CustomerControl.name' => 'John ind.'
        ])->first();

        $this->assertEquals('11', $task->id);
    }

    /**
     *
     */
    public function test_dynamic_far_join()
    {
        $task = Task::where('target#DocumentControl.customer.name', 'John ind.')->first();

        $this->assertEquals('10', $task->id);
    }

    /**
     *
     */
    public function test_complex_join()
    {
        $task = Task::where('target#DocumentControl.uploader#user.name', 'John Doe')->first();

        $this->assertEquals('10', $task->id);
    }

    /**
     *
     */
    public function test_save_all()
    {
        $task = Task::with('target#DocumentControl.uploader')->get(10);
        $task->target->uploader->name = 'save all';

        Task::repository()->saveAll($task, 'target#DocumentControl.uploader');

        $user = User::get($task->target->uploader->id);

        $this->assertEquals('save all', $user->name);
    }

    /**
     *
     */
    public function test_delete_all()
    {
        $task = Task::with('target#DocumentControl.uploader')->get(10);

        Task::repository()->deleteAll($task, 'target#DocumentControl.uploader');

        $this->assertNull(User::get($task->target->uploader->id));
        $this->assertNull(Document::get($task->target->id));
        $this->assertNull(Task::get($task->id));
    }

    /**
     *
     */
    public function test_eager_relation()
    {
        $task = Task::get(11);

        $this->assertEquals('John ind.', $task->targetEager->name);
        $this->assertTrue($task->relation('targetEager')->isLoaded());
    }

    /**
     *
     */
    public function test_without_on_eager_relation()
    {
        $task = Task::without('targetEager')->get(10);

        $this->assertNull($task->targetEager);
        $this->assertFalse($task->relation('targetEager')->isLoaded());
    }

    /**
     *
     */
    public function test_load_collection_without_subrelation()
    {
        $repository = Prime::repository('Bdf\Prime\Task');

        $tasks = $repository->without('targetEager')->by('id')->all();

        $relation = $repository->relation('targetEager');
        $relation->load(EntityIndexer::fromArray($repository->mapper(), $tasks), [], [], ['uploader']);

        $this->assertInstanceOf(Customer::class, $tasks[11]->targetEager);
        $this->assertInstanceOf(DocumentEager::class, $tasks[100]->targetEager);

        $this->assertNull($tasks[100]->targetEager->uploader);
    }

    /**
     *
     */
    public function test_load_without_subrelation()
    {
        $repository = Prime::repository('Bdf\Prime\Task');

        $task = $repository->without('targetEager')->get(100);

        $relation = $repository->relation('targetEager');
        $relation->load(new SingleEntityIndexer(Task::mapper(), $task), [], [], ['uploader']);

        $this->assertNull($task->targetEager->uploader);
    }

    /**
     *
     */
    public function test_load_twice_should_not_reload()
    {
        $task = $this->pack()->get('documentTask');

        $task->load('target');

        $this->assertTrue($task->relation('target')->isLoaded());
        $this->assertFalse($task->target->relation('uploader')->isLoaded());
        $loadedTarget = $task->target;

        $task->load('target');
        $this->assertSame($loadedTarget, $task->target);

        $task->load('target.uploader');
        $this->assertSame($loadedTarget, $task->target);
        $this->assertTrue($task->target->relation('uploader')->isLoaded());
    }

    /**
     *
     */
    public function test_reload()
    {
        $task = $this->pack()->get('documentTask');

        $task->load('target');
        $loadedTarget = $task->target;

        $task->reload('target');
        $this->assertNotSame($loadedTarget, $task->target);
    }
}
