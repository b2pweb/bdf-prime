<?php

namespace Bdf\Prime\Entity;

require_once __DIR__.'/../../tests/_files/PrimeTestCase.php';
require_once __DIR__.'/../../tests/_files/HydratorGeneration.php';
require_once __DIR__.'/../../tests/_files/array_hydrator_entities.php';

use Bdf\Prime\ArrayHydratorTestEntity;
use Bdf\Prime\Bench\HydratorGeneration;
use Bdf\Prime\EmbeddedEntity;
use Bdf\Prime\Entity\Hydrator\ArrayHydrator;
use Bdf\Prime\Entity\Hydrator\HydratorGeneratedInterface;
use Bdf\Prime\PrimeTestCase;
use Bdf\Serializer\Serializer;
use Bdf\Serializer\SerializerBuilder;

/**
 * @Revs(1000)
 */
class EntityHydrationBench extends \BenchCaseAdapter
{
    use PrimeTestCase;
    use HydratorGeneration;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var int
     */
    protected $times;

    /**
     * @var ArrayHydratorTestEntity
     */
    protected $entity;

    /**
     * @var ArrayHydrator
     */
    protected $hydrator;

    /**
     * @var HydratorGeneratedInterface
     */
    protected $generated;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->configurePrime();

        $this->data = [
            'name' => 'Bob',
            'phone' => '0123654987',
            'password' => 'bob',
            'ref' => [
                'id' => 15
            ],
            'ref2' => new EmbeddedEntity(123)
        ];

        $this->times = 1000;

        $this->entity = new ArrayHydratorTestEntity();

        $this->hydrator = new ArrayHydrator();
        $this->serializer = SerializerBuilder::create()->build();
        $this->generated = $this->createGeneratedHydrator(ArrayHydratorTestEntity::class);

        $this->entity->import($this->data);
    }

    /**
     * @Groups({"import"})
     */
    public function bench_ArrayInjector_fromArray()
    {
        $this->entity->import($this->data);
    }

    /**
     * @Groups({"import"})
     *
     */
    public function bench_ArrayHydrator_hydrate()
    {
        $this->hydrator->hydrate($this->entity, $this->data);
    }

    /**
     * @Groups({"import"})
     *
     */
    public function bench_Serializer_hydrate()
    {
        $this->serializer->fromArray($this->data, $this->entity);
    }

    /**
     * @Groups({"import"})
     *
     */
    public function bench_GeneratedHydrator()
    {
        $this->generated->hydrate($this->entity, $this->data);
    }

    /**
     * @Groups({"export"})
     *
     */
    public function bench_ArrayInjector_toArray()
    {
        return $this->entity->export();
    }

    /**
     * @Groups({"export"})
     *
     */
    public function bench_ArrayHydrator_extract()
    {
        return $this->hydrator->extract($this->entity);
    }

    /**
     * @Groups({"export"})
     *
     */
    public function bench_Serializer_toArray()
    {
        return $this->serializer->toArray($this->entity);
    }

    /**
     * @Groups({"export"})
     *
     */
    public function bench_Generated_extract()
    {
        return $this->generated->extract($this->entity);
    }

    /**
     * @Groups({"export-filter"})
     *
     */
    public function bench_ArrayInjector_toArray_with_filter()
    {
        return $this->entity->export(['name', 'phone', 'ref']);
    }

    /**
     * @Groups({"export-filter"})
     *
     */
    public function bench_ArrayHydrator_extract_with_filter()
    {
        return $this->hydrator->extract($this->entity, ['name', 'phone', 'ref']);
    }

    /**
     * @Groups({"export-filter"})
     *
     */
    public function bench_Serializer_toArray_with_filter()
    {
        return $this->serializer->toArray($this->entity, ['include' => ['name', 'phone', 'ref']]);
    }

    /**
     * @Groups({"export-filter"})
     *
     */
    public function bench_Generated_extract_with_filter()
    {
        return $this->generated->extract($this->entity, ['name', 'phone', 'ref']);
    }
}
