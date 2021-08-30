<?php

namespace Bdf\Prime\Bench;

use Bdf\Prime\Entity\Hydrator\HydratorGeneratedInterface;
use Bdf\Prime\Entity\Hydrator\HydratorGenerator;
use Bdf\Prime\Entity\Hydrator\HydratorRegistry;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Prime;
use LogicException;

/**
 * HydratorGeneration
 */
trait HydratorGeneration
{
    /**
     * @var HydratorRegistry
     */
    protected $__registry;

    protected $__pendingGeneration = [];

    /**
     * Create the generated hydrator
     *
     * @param string $entityClass
     *
     * @return HydratorGeneratedInterface
     */
    public function createGeneratedHydrator($entityClass)
    {
        if ($this->__registry === null) {
            $this->__registry = new HydratorRegistry();
        }

        if ($this->__registry->get($entityClass) instanceof HydratorGeneratedInterface) {
            return $this->__registry->get($entityClass);
        }

        if (isset($this->__pendingGeneration[$entityClass])) {
            throw new LogicException("Recursion for " . $entityClass . " : " . implode(', ', array_keys($this->__pendingGeneration)));
        }

        $this->__pendingGeneration[$entityClass] = true;

        $mapper = Prime::service()->repository($entityClass)->mapper();
        $generator = new HydratorGenerator(Prime::service(), $mapper, $entityClass);

        $hydratorClass = $generator->hydratorFullClassName();

        if (!class_exists($hydratorClass)) {
            eval(substr($generator->generate(), 5));
        }

        $arguments = [];

        foreach ($hydratorClass::embeddedPrimeClasses() as $class) {
            $arguments[] = $this->createGeneratedHydrator($class);
        }

        $hydrator = new $hydratorClass(...$arguments);
        $hydrator->setPrimeInstantiator(Prime::service()->instantiator());
        $hydrator->setPrimeMetadata($mapper->metadata());

        $this->__registry->add($entityClass, $hydrator);

        return $hydrator;
    }

    /**
     * Generate and register to prime hydrators
     *
     * @param string ...$entityClasses
     */
    private function setUpGeneratedHydrators(string... $entityClasses): void
    {
        $r = new \ReflectionProperty(Mapper::class, 'hydrator');
        $r->setAccessible(true);

        foreach ($entityClasses as $entityClass) {
            $hydrator = $this->createGeneratedHydrator($entityClass);
            $r->setValue(Prime::service()->repository($entityClass)->mapper(), $hydrator);
        }
    }
}
