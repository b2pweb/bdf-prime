<?php

namespace Bdf\Prime\Query;

require_once __DIR__ . '/../_files/BenchData.php';

use Bdf\Prime\Bench\BenchData;
use Bdf\Prime\Bench\User;
use Bdf\Prime\Cache\ArrayCache;
use Bdf\Prime\Connection\ConnectionConfig;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\Locatorizable;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Prime\ServiceLocator;
use Bdf\Prime\Types\ArrayType;
use BenchCaseAdapter;

/**
 * Bench wrap as results
 *
 * @Revs(10)
 * @Iterations(5)
 */
class QueryWrapAsBench extends BenchCaseAdapter
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
        $this->data->addAllData();

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
     *
     */
    public function bench_no_wrap()
    {
        $this->repository->all();
    }

    /**
     *
     */
    public function bench_wrapAs_collection()
    {
        $this->repository->wrapAs('collection')->all();
    }

    /**
     *
     */
    public function bench_wrapAs_array()
    {
        $this->repository->wrapAs('array')->all();
    }
}
