<?php

namespace Bdf\Prime\Query;

require_once __DIR__ . '/../_files/BenchData.php';

use Bdf\Prime\Bench\BenchData;
use Bdf\Prime\Bench\User;
use Bdf\Prime\Cache\ArrayCache;
use Bdf\Prime\Collection\EntityCollection;
use Bdf\Prime\Connection\ConnectionConfig;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\Locatorizable;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Prime\Repository\Write\BufferedWriter;
use Bdf\Prime\ServiceLocator;
use Bdf\Prime\Types\ArrayType;
use BenchCaseAdapter;

/**
 * Bench bulk operations using collections
 *
 * @Revs(10)
 */
class BulkOperationBench extends BenchCaseAdapter
{
    /**
     * @var ServiceLocator
     */
    protected $prime;

    /**
     * @var BenchData
     */
    protected $data;

    /**
     * @var ArrayCache
     */
    protected $cache;

    /**
     * @var EntityRepository
     */
    protected $repository;



    public function setUp()
    {
        $this->cache = new ArrayCache();
        $this->prime = new ServiceLocator();
        $this->prime->connections()->declareConnection('test', BENCH_CONNECTION);
        $this->prime->connection('test')->getConfiguration()->getTypes()->register(ArrayType::class, 'array');
        Locatorizable::configure($this->prime);

        $this->data = new BenchData($this->prime);
        //$this->data->addAllData();
        $this->data->addBulkUsers();

        $this->repository = $this->prime->repository(User::class);
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        $this->data->clear();
    }

    /**
     * @Groups({"update"})
     */
    public function bench_bulk_update_native()
    {
        /** @var User[] $users */
        $users = $this->repository->all();

        foreach ($users as $user) {
            $user->roles = [1];
            $this->repository->update($user, ['roles']);
        }
    }

    /**
     * @Groups({"update"})
     */
    public function bench_bulk_update_collection()
    {
        /** @var EntityCollection $users */
        $users = $this->repository->wrapAs('collection')->all();
        $users->update(['roles' => [1]]);
    }

    /**
     * @Groups({"update"})
     */
    public function bench_bulk_update_buffered_writer()
    {
        /** @var User[] $users */
        $users = $this->repository->all();
        $writer = new BufferedWriter($this->repository);

        foreach ($users as $user) {
            $user->roles = [1];
            $writer->update($user, ['attributes' => ['roles']]);
        }

        $writer->flush();
    }

    /**
     * @Groups({"load"})
     */
    public function bench_bulk_load_native()
    {
        /** @var User[] $users */
        $users = $this->repository->all();

        foreach ($users as $user) {
            $this->repository->loadRelations($user, 'customer');
        }
    }

    /**
     * @Groups({"load"})
     */
    public function bench_bulk_load_collection()
    {
        /** @var EntityCollection $users */
        $users = $this->repository->wrapAs('collection')->all();
        $users->load('customer');
    }

    /**
     * @Groups({"delete"})
     */
    public function bench_bulk_delete_native()
    {
        /** @var User[] $users */
        $users = $this->repository->all();

        foreach ($users as $user) {
            $this->repository->delete($user);
        }
    }

    /**
     * @Groups({"delete"})
     */
    public function bench_bulk_delete_collection()
    {
        /** @var EntityCollection $users */
        $users = $this->repository->wrapAs('collection')->all();
        $users->delete();
    }

    /**
     * @Groups({"delete"})
     */
    public function bench_bulk_delete_writer()
    {
        $writer = $this->repository->writer();

        foreach ($this->repository->all() as $user) {
            $writer->delete($user);
        }
    }

    /**
     * @Groups({"delete"})
     */
    public function bench_bulk_delete_buffered_writer()
    {
        $writer = new BufferedWriter($this->repository);

        foreach ($this->repository->all() as $user) {
            $writer->delete($user);
        }

        $writer->flush();
    }

    /**
     * @Groups({"save"})
     */
    public function bench_bulk_save_native()
    {
        /** @var User[] $users */
        $users = $this->repository->all();

        foreach ($users as $user) {
            $this->repository->save($user);
        }
    }

    /**
     * @Groups({"save"})
     */
    public function bench_bulk_save_collection()
    {
        /** @var EntityCollection $users */
        $users = $this->repository->wrapAs('collection')->all();
        $users->save();
    }
}
