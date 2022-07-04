<?php

namespace Bdf\Prime;

require_once __DIR__ . '/_files/BenchData.php';

use Bdf\Prime\Bench\BenchData;
use Bdf\Prime\Bench\User as BenchUser;
use Bdf\Prime\Cache\ArrayCache;
use Bdf\Prime\Connection\ConnectionConfig;
use Bdf\Prime\Types\ArrayType;

/**
 * @Revs(100)
 */
class PrimeBench extends \BenchCaseAdapter
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



    public function setUp()
    {
        $this->cache = new ArrayCache();
        $this->prime = new ServiceLocator();
        $this->prime->connections()->declareConnection('test', BENCH_CONNECTION);
        $this->prime->connection('test')->getConfiguration()->getTypes()->register(ArrayType::class, 'array');
        Locatorizable::configure($this->prime);

        $this->data = new BenchData($this->prime);
        $this->data->addAllData();
    }

    /**
     *
     */
    public function tearDown()
    {
        $this->data->clear();
    }

    /**
     * @ParamProviders({"queryParams"})
     */
    public function bench($params)
    {
        list($name, $cache, $relations, $nb) = $params;

        $query = $this->prime->repository(BenchUser::class)->queries()->builder();

        if ($cache) {
            $query->setCache($this->cache)->useCache();
        }

        $query
            ->with($relations)
            ->limit(100)
            ->all();
    }

    /**
     * @ParamProviders({"queryParams"})
     */
    public function bench_keyValue($params)
    {
        list($name, $cache, $relations, $nb) = $params;

        $query = $this->prime->repository(BenchUser::class)->queries()->keyValue();

        if ($cache) {
            $query->setCache($this->cache)->useCache();
        }

        $query
            ->with($relations)
            ->limit(100)
            ->all();
    }
    
    /**
     * 
     */
    public function queryParams()
    {
        return [
            ['loading class          ', true, 'customer.packs', 1],
            ['1 -cache               ', false, [], 1],
            ['1 +cache               ', true, [], 1],
            ['1 -cache hasOne        ', false, 'customer', 1],
            ['1 +cache hasOne        ', true, 'customer', 1],
            ['1 -cache hasOne hasMany', false, 'customer.packs', 1],
            ['1 +cache hasOne hasMany', true, 'customer.packs', 1],

            ['2 -cache               ', false, [], 2],
            ['2 +cache               ', true, [], 2],
            ['2 -cache hasOne        ', false, 'customer', 2],
            ['2 +cache hasOne        ', true, 'customer', 2],
            ['2 -cache hasOne hasMany', false, 'customer.packs', 2],
            ['2 +cache hasOne hasMany', true, 'customer.packs', 2],
        ];
    }
}

function convert($size) {
    return number_format($size/1024).' kb';
}

function profile($callback, $times) {
    $bench = [
        'queries'      => 0,
        'time'         => 0,
        'memory'       => 0,
    ];
    $start = microtime(true);
    $memory = memory_get_usage(true);
        
    for ($i = 0; $i < $times; $i++) {
        $callback();
    }
        
    $end = microtime(true);
    
    $delta = ($end - $start);
    
    $bench['time'] =  $delta;
    $bench['queries'] =  $times / $delta;
    $bench['memory'] =  memory_get_usage(true) - $memory;
    
    return $bench;
}