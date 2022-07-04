<?php

namespace Bdf\Prime;

require_once __DIR__ . '/_files/BenchData.php';

use Bdf\Prime\Bench\BenchData;
use Bdf\Prime\Bench\EntityArrayOf;
use Bdf\Prime\Bench\EntityNotTypedArray;
use Bdf\Prime\Cache\ArrayCache;
use Bdf\Prime\Connection\ConnectionConfig;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Prime\Types\ArrayType;
use BenchCaseAdapter;

/**
 * @Revs(100)
 */
class TypeBench extends BenchCaseAdapter
{
    const SCALAR_COUNT = 10;
    const DATEIME_COUNT = 10;

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
    protected $typedRepository;

    /**
     * @var EntityRepository
     */
    protected $notTypedRepository;


    public function setUp()
    {
        $this->cache = new ArrayCache();
        $this->prime = new ServiceLocator();
        $this->prime->connections()->declareConnection('test', BENCH_CONNECTION);
        $this->prime->connection('test')->getConfiguration()->getTypes()->register(ArrayType::class, 'array');
        Locatorizable::configure($this->prime);

        $this->data = new BenchData($this->prime);
        $this->data->register([EntityArrayOf::class, EntityNotTypedArray::class]);

        $entities = [];

        for ($i = 0; $i < 100; ++$i) {
            $entities[] = new EntityArrayOf([
                'floats'   => $this->randArray('double', self::SCALAR_COUNT),
                'booleans' => $this->randArray('boolean', self::SCALAR_COUNT),
                'dates'    => $this->randArray('datetime', self::DATEIME_COUNT),
            ]);

            $entities[] = new EntityNotTypedArray([
                'floats'   => $this->randArray('double', self::SCALAR_COUNT),
                'booleans' => $this->randArray('boolean', self::SCALAR_COUNT),
                'dates'    => array_map(function (\DateTime $dateTime) { return $dateTime->format(\DateTime::ISO8601); }, $this->randArray('datetime', self::DATEIME_COUNT)),
            ]);
        }

        $this->data->push($entities);

        $this->typedRepository = $this->prime->repository(EntityArrayOf::class);
        $this->notTypedRepository = $this->prime->repository(EntityNotTypedArray::class);
    }

    /**
     * @Groups({"array", "read"})
     */
    public function bench_all_typed()
    {
        $this->typedRepository->all();
    }

    /**
     * @Groups({"array", "read"})
     */
    public function bench_all_not_typed()
    {
        $this->notTypedRepository->all();
    }

    /**
     * @Groups({"array", "read"})
     */
    public function bench_all_not_typed_with_cast()
    {
        /** @var EntityNotTypedArray $entity */
        foreach ($this->notTypedRepository->all() as $entity) {
            $entity->booleans = array_map('boolval', $entity->booleans);
            $entity->floats = array_map('doubleval', $entity->floats);
            $entity->dates = array_map(function ($str) { return \DateTime::createFromFormat(\DateTime::ISO8601, $str); }, $entity->dates);
        }
    }

    /**
     * @Groups({"array", "insert"})
     */
    public function bench_insert_typed()
    {
        $this->typedRepository->save(
            new EntityArrayOf([
                'floats'   => $this->randArray('double', self::SCALAR_COUNT),
                'booleans' => $this->randArray('boolean', self::SCALAR_COUNT),
                'dates'    => $this->randArray('datetime', self::DATEIME_COUNT),
            ])
        );
    }

    /**
     * @Groups({"array", "insert"})
     */
    public function bench_insert_not_typed()
    {
        $this->notTypedRepository->save(
            new EntityNotTypedArray([
                'floats'   => $this->randArray('double', self::SCALAR_COUNT),
                'booleans' => $this->randArray('boolean', self::SCALAR_COUNT),
                'dates'    => array_map(function (\DateTime $dateTime) { return $dateTime->format(\DateTime::ISO8601); }, $this->randArray('datetime', self::DATEIME_COUNT)),
            ])
        );
    }

    private function randArray($type, $size)
    {
        $array = [];

        for (; $size > 0; --$size) {
            $value = rand(0, 100);

            switch ($type) {
                case 'double':
                    $value /= 100;
                    break;

                case 'boolean':
                    $value = $value >= 50;
                    break;

                case 'datetime':
                    $value = new \DateTime('+'.$value.'days');
                    break;
            }

            $array[] = $value;
        }

        return $array;
    }
}
