<?php

namespace Bdf\Prime\Query;

require_once __DIR__ . '/../_files/BenchData.php';

use Bdf\Prime\Bench\BenchData;
use Bdf\Prime\Bench\User;
use Bdf\Prime\Cache\ArrayCache;
use Bdf\Prime\Connection\ConnectionConfig;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\Locatorizable;
use Bdf\Prime\Query\Custom\BulkInsert\BulkInsertQuery;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Prime\ServiceLocator;
use Bdf\Prime\Types\ArrayType;
use BenchCaseAdapter;

/**
 * Bench custom queries
 *
 * @Revs(10)
 * @Warmup(1)
 */
class CustomQueriesBench extends BenchCaseAdapter
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
        $this->data->addBulkUsers();

        $this->repository = $this->prime->repository(User::class);
    }

    /**
     * @Groups({"count"})
     */
    public function bench_count()
    {
        return $this->repository->count(['id' => '23000123']) > 0;
    }

    /**
     * @Groups({"count"})
     */
    public function bench_countKeyValue()
    {
        return $this->repository->queries()->countKeyValue(['id' => '23000123']) > 0;
    }

    /**
     * @Groups({"findById"})
     */
    public function bench_findById()
    {
        return $this->repository->queries()->findById('23000123');
    }

    /**
     * @Groups({"findById"})
     */
    public function bench_builder_get()
    {
        return $this->repository->builder()->get('23000123');
    }

    /**
     * @Groups({"search"})
     */
    public function bench_search_keyValue()
    {
        return $this->repository->keyValue([
            'name'        => 'user Common 123',
            'customer.id' => '330000',
        ])->first();
    }

    /**
     * @Groups({"search"})
     */
    public function bench_search_builder()
    {
        return $this->repository->builder()->where([
            'name'        => 'user Common 123',
            'customer.id' => '330000',
        ])->first();
    }

    /**
     * @Groups({"delete"})
     */
    public function bench_delete_keyValue()
    {
        $id = '23000'.str_pad(rand(1, 500), 3, '0', STR_PAD_LEFT);
        return $this->repository->keyValue('id', $id)->delete();
    }

    /**
     * @Groups({"delete"})
     */
    public function bench_delete_keyValue_cached()
    {
        static $query;

        if (!$query) {
            $query = $this->repository->keyValue();
        }

        $id = '23000'.str_pad(rand(1, 500), 3, '0', STR_PAD_LEFT);
        return $query->where('id', $id)->delete();
    }

    /**
     * @Groups({"delete"})
     */
    public function bench_delete_builder()
    {
        $id = '23000'.str_pad(rand(1, 500), 3, '0', STR_PAD_LEFT);
        return $this->repository->builder()->where('id', $id)->delete();
    }

    /**
     * @Groups({"update"})
     */
    public function bench_update_builder()
    {
        $id = '23000'.str_pad(rand(1, 500), 3, '0', STR_PAD_LEFT);
        return $this->repository->builder()->where('id', $id)->update(['name' => 'Bob']);
    }

    /**
     * @Groups({"update"})
     */
    public function bench_update_keyValue()
    {
        $id = '23000'.str_pad(rand(1, 500), 3, '0', STR_PAD_LEFT);
        return $this->repository->keyValue('id', $id)->update(['name' => 'Bob']);
    }

    /**
     * @Groups({"update"})
     */
    public function bench_update_keyValue_cached()
    {
        static $query;

        if (!$query) {
            $query = $this->repository->keyValue();
        }

        $id = '23000'.str_pad(rand(1, 500), 3, '0', STR_PAD_LEFT);
        return $query->where('id', $id)->update(['name' => 'Bob']);
    }

    /**
     * @Groups({"insert"})
     */
    public function bench_insert_bulk()
    {
        /** @var BulkInsertQuery $insert */
        $insert = $this->repository->queries()->make(BulkInsertQuery::class);
        $insert->bulk();

        for ($i = 0; $i < 100; ++$i) {
            $insert->values([
                'id'          => 6000000 + $i,
                'name'        => 'bulk user '.$i,
                'roles'       => ['2'],
                'customer.id' => 1,
            ]);
        }

        $insert->execute();

        // Clear data set
        $this->repository->where('id', 'between', [6000000, 7000000])->delete();
    }

    /**
     * @Groups({"insert"})
     */
    public function bench_insert_transaction()
    {
        $this->repository->transaction(function () {
            /** @var BulkInsertQuery $insert */
            $insert = $this->repository->queries()->make(BulkInsertQuery::class);

            for ($i = 0; $i < 100; ++$i) {
                $insert->values([
                    'id'          => 6000000 + $i,
                    'name'        => 'bulk user '.$i,
                    'roles'       => ['2'],
                    'customer.id' => 1,
                ]);
                $insert->execute();
            }
        });

        // Clear data set
        $this->repository->where('id', 'between', [6000000, 7000000])->delete();
    }

    /**
     * @Groups({"insert"})
     */
    public function bench_insert_builder()
    {
        $this->repository->transaction(function () {
            for ($i = 0; $i < 100; ++$i) {
                $this->repository->builder()->insert([
                    'id'          => 6000000 + $i,
                    'name'        => 'bulk user '.$i,
                    'roles'       => ['2'],
                    'customer.id' => 1,
                ]);
            }
        });

        // Clear data set
        $this->repository->where('id', 'between', [6000000, 7000000])->delete();
    }
}
