<?php

namespace Bdf\Prime\Query;

require_once __DIR__ . '/../_files/BenchData.php';

use Bdf\Prime\Bench\BenchData;
use Bdf\Prime\Bench\User as BenchUser;
use Bdf\Prime\Cache\ArrayCache;
use Bdf\Prime\Connection\ConnectionConfig;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\Locatorizable;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Prime\ServiceLocator;
use Bdf\Prime\Types\ArrayType;

/**
 * Bench query generation with dynamic join
 * @Revs(10)
 */
class JoinBench extends \BenchCaseAdapter
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

        $this->repository = $this->prime->repository(BenchUser::class);
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        $this->data->clear();
    }


    /**
     * @ParamProviders({"joins"})
     */
    public function bench($params)
    {
        list($clause, $value) = $params;

        $this->repository
            ->where($clause, $value)
            ->toSql();
    }

    /**
     *
     */
    public function joins()
    {
        return [
            ['customer.packs.label', 'Simply'],
            ['customer.name', 'customer Common name'],
            ['customer.id', '330000'],
        ];
    }
}