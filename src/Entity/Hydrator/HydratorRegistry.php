<?php

namespace Bdf\Prime\Entity\Hydrator;

/**
 * HydratorRegistry
 */
class HydratorRegistry
{
    /**
     * @var HydratorInterface
     */
    protected $baseHydrator;

    /**
     * @var HydratorInterface[]
     */
    protected $hydrators = [];

    /**
     * @var callable[]
     */
    protected $factories = [];

    /**
     * Set the base hydrator.
     *
     * @param HydratorInterface $baseHydrator
     *
     * @return void
     */
    public function setBaseHydrator(HydratorInterface $baseHydrator): void
    {
        $this->baseHydrator = $baseHydrator;
    }

    /**
     * Register all hydrators
     *
     * @param HydratorInterface[] $hydrators
     *
     * @return void
     */
    public function setHydrators(array $hydrators): void
    {
        $this->hydrators = $hydrators;
    }

    /**
     * Register a new Hydrator
     *
     * @param string $entityClass
     * @param HydratorInterface $hydrator
     *
     * @return void
     */
    public function add($entityClass, HydratorInterface $hydrator): void
    {
        $this->hydrators[$entityClass] = $hydrator;
    }

    /**
     * Register all hydrator factory
     *
     * @param callable[] $factories
     *
     * @return void
     */
    public function setFactories(array $factories): void
    {
        $this->factories = $factories;
    }

    /**
     * Register a new hydrator factory
     *
     * @param string $entityClass
     * @param callable $factory
     *
     * @return void
     */
    public function factory($entityClass, $factory): void
    {
        $this->factories[$entityClass] = $factory;
    }

    /**
     * Get the hydrator object
     *
     * @param string $entityClass
     *
     * @return HydratorInterface
     */
    public function get($entityClass)
    {
        if (isset($this->hydrators[$entityClass])) {
            return $this->hydrators[$entityClass];
        }

        if (isset($this->factories[$entityClass])) {
            $fn = $this->factories[$entityClass];
            return $this->hydrators[$entityClass] = $fn($this);
        }

        if ($this->baseHydrator === null) {
            $this->baseHydrator = new ArrayHydrator();
        }

        return $this->baseHydrator;
    }
}
