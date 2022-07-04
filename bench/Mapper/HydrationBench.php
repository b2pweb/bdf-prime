<?php

namespace Bdf\Prime\Mapper;

require_once __DIR__.'/../../tests/_files/PrimeTestCase.php';
require_once __DIR__.'/../../tests/_files/HydratorGeneration.php';
require_once __DIR__.'/../../tests/_files/entity.php';
require_once __DIR__.'/../../tests/_files/embedded.php';

use Bdf\Prime\Bench\HydratorGeneration;
use Bdf\Prime\Entity\Hydrator\HydratorGeneratedInterface;
use Bdf\Prime\Entity\Hydrator\MapperHydrator;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\TestEmbeddedEntity;
use Bdf\Prime\TestEntity;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * @Revs(3000)
 */
class HydrationBench extends \BenchCaseAdapter
{
    use PrimeTestCase;
    use HydratorGeneration;

    /**
     * @var Mapper
     */
    protected $mapper;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var array
     */
    protected $flatData;

    /**
     * @var HydratorGeneratedInterface
     */
    protected $generatedHydrator;

    /**
     * @var MapperHydrator
     */
    protected $defaultHydrator;

    protected $entity;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->primeStart();

        $this->mapper = TestEntity::repository()->mapper();
        $this->data = [
            "id" => 123,
            "name" => "toto",
            "dateInsert" => new DateTime(),
            "foreign" => [
                "id" => 1587,
                "name" => "Paul",
                "city" => "Paris"
            ]
        ];

        $this->flatData = [
            "id" => 123,
            "name" => "toto",
            "dateInsert" => "2017-01-08 15:32:21",
            "foreign.id" => 1587
        ];

        $this->generatedHydrator = $this->createHydrator();
        $this->defaultHydrator = new MapperHydrator();

        $this->entity = new TestEntity($this->data);
    }

    /**
     * @Groups({"prepareToRepository"})
     */
    public function bench_prepareToRepository()
    {
        $this->mapper->setHydrator($this->defaultHydrator);

        $this->mapper->prepareToRepository($this->entity);
    }

    /**
     * @Groups({"prepareToRepository"})
     *
     */
    public function bench_prepareToRepository_hydrator()
    {
        $this->mapper->setHydrator($this->generatedHydrator);

        $this->mapper->prepareToRepository($this->entity);
    }

    /**
     * @Groups({"prepareFromRepository"})
     *
     */
    public function bench_prepareFromRepository()
    {
        $this->mapper->setHydrator($this->defaultHydrator);

        $platform = $this->mapper->repository()->connection()->platform();

        return $this->mapper->prepareFromRepository($this->flatData, $platform);
    }

    /**
     * @Groups({"prepareFromRepository"})
     *
     */
    public function bench_prepareFromRepository_hydrator()
    {
        $this->mapper->setHydrator($this->generatedHydrator);

        $platform = $this->mapper->repository()->connection()->platform();

        return $this->mapper->prepareFromRepository($this->flatData, $platform);
    }

    /**
     * @Groups({"extractOne"})
     *
     */
    public function bench_extractOne()
    {
        $this->mapper->setHydrator($this->defaultHydrator);

        $this->mapper->extractOne($this->entity, "id");
        $this->mapper->extractOne($this->entity, "name");
        $this->mapper->extractOne($this->entity, "foreign.id");
        $this->mapper->extractOne($this->entity, "foreign");
    }

    /**
     * @Groups({"extractOne"})
     *
     */
    public function bench_extractOne_hydrator()
    {
        $this->mapper->setHydrator($this->generatedHydrator);

        $this->mapper->extractOne($this->entity, "id");
        $this->mapper->extractOne($this->entity, "name");
        $this->mapper->extractOne($this->entity, "foreign.id");
        $this->mapper->extractOne($this->entity, "foreign");
    }

    /**
     * @Groups({"hydrateOne"})
     *
     */
    public function bench_hydrateOne()
    {
        $this->mapper->setHydrator($this->defaultHydrator);

        $this->mapper->hydrateOne($this->entity, "id", 5);
        $this->mapper->hydrateOne($this->entity, "name", "Test");
        $this->mapper->hydrateOne($this->entity, "foreign.id", 123);
        $this->mapper->hydrateOne($this->entity, "foreign", new TestEmbeddedEntity());
    }

    /**
     * @Groups({"hydrateOne"})
     *
     */
    public function bench_hydrateOne_hydrator()
    {
        $this->mapper->setHydrator($this->generatedHydrator);

        $this->mapper->hydrateOne($this->entity, "id", 5);
        $this->mapper->hydrateOne($this->entity, "name", "Test");
        $this->mapper->hydrateOne($this->entity, "foreign.id", 123);
        $this->mapper->hydrateOne($this->entity, "foreign", new TestEmbeddedEntity());
    }

    /**
     * @return HydratorGeneratedInterface
     */
    protected function createHydrator()
    {
        return $this->createGeneratedHydrator(TestEntity::class);
    }
}
