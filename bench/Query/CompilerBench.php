<?php

namespace Bdf\Prime\Query;

require_once __DIR__ . '/../_files/BenchData.php';

use Bdf\Prime\Bench\BenchData;
use Bdf\Prime\Bench\User;
use Bdf\Prime\Cache\ArrayCache;
use Bdf\Prime\Connection\ConnectionConfig;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\Locatorizable;
use Bdf\Prime\Query\Compiler\Preprocessor\OrmPreprocessor;
use Bdf\Prime\Query\Compiler\SqlCompiler;
use Bdf\Prime\Query\Custom\KeyValue\KeyValueQuery;
use Bdf\Prime\Query\Custom\KeyValue\KeyValueSqlCompiler;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Prime\ServiceLocator;
use Bdf\Prime\Types\ArrayType;
use BenchCaseAdapter;

/**
 * @Revs(100)
 * @Warmup(1)
 */
class CompilerBench extends BenchCaseAdapter
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

    /**
     * @var KeyValueSqlCompiler
     */
    protected $keyValueCompiler;

    /**
     * @var SqlCompiler
     */
    protected $sqlCompiler;



    public function setUp()
    {
        $this->cache = new ArrayCache();
        $this->prime = new ServiceLocator();
        $this->prime->connections()->declareConnection('test', BENCH_CONNECTION);
        $this->prime->connection('test')->getConfiguration()->getTypes()->register(ArrayType::class, 'array');
        Locatorizable::configure($this->prime);

        $this->data = new BenchData($this->prime);
        $this->data->register([User::class]);

        $this->repository = $this->prime->repository(User::class);

        $this->keyValueCompiler = new KeyValueSqlCompiler($this->repository->connection());
        $this->sqlCompiler = new SqlCompiler($this->repository->connection());
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        $this->data->clear();
    }

    /**
     * @Groups({"select"})
     */
    public function bench_compileSelect_KeyValue()
    {
        $query = $this->repository->connection()->make(KeyValueQuery::class, new OrmPreprocessor($this->repository));
        $query->from($this->repository->metadata()->table);
        (new QueryRepositoryExtension($this->repository))->apply($query);

        $query->where(['name' => 'John', 'customer.id' => 5]);

        $this->keyValueCompiler->compileSelect($query);
    }

    /**
     * @Groups({"delete"})
     */
    public function bench_compileDelete_KeyValue()
    {
        $query = $this->repository->connection()->make(KeyValueQuery::class, new OrmPreprocessor($this->repository));
        $query->from($this->repository->metadata()->table);
        (new QueryRepositoryExtension($this->repository))->apply($query);

        $query->where(['name' => 'John', 'customer.id' => 5]);

        $this->keyValueCompiler->compileDelete($query);
    }

    /**
     * @Groups({"select"})
     */
    public function bench_compileSelect_default()
    {
        $query = $this->repository->builder();
        $query->where(['name' => 'John', 'customer.id' => 5]);

        $this->sqlCompiler->compileSelect($query);
    }

    /**
     * @Groups({"delete"})
     */
    public function bench_compileDelete_default()
    {
        $query = $this->repository->builder();
        $query->where(['name' => 'John', 'customer.id' => 5]);

        $this->sqlCompiler->compileDelete($query);
    }
}
