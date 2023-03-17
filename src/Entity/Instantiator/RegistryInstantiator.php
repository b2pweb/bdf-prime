<?php

namespace Bdf\Prime\Entity\Instantiator;

/**
 * RegistryInstantiator
 */
class RegistryInstantiator implements InstantiatorInterface
{
    /**
     * @var InstantiatorInterface[]
     */
    protected $registry = [];

    /**
     * @var InstantiatorInterface
     */
    protected $defaultInstantiator;

    /**
     * Instantiator constructor.
     *
     * @param null|InstantiatorInterface $defaultInstantiator
     */
    public function __construct(InstantiatorInterface $defaultInstantiator = null)
    {
        $this->defaultInstantiator = $defaultInstantiator;
    }

    /**
     * Register an instantiator for this class
     *
     * @param class-string $className
     * @param InstantiatorInterface $instantiator
     *
     * @return void
     */
    public function register(string $className, InstantiatorInterface $instantiator): void
    {
        $this->registry[$className] = $instantiator;
    }

    /**
     * {@inheritdoc}
     */
    public function instantiate($className, $hint = null): object
    {
        if (isset($this->registry[$className])) {
            return $this->registry[$className]->instantiate($className, $hint);
        }

        return $this->getDefaultInstantiator()->instantiate($className, $hint);
    }

    /**
     * Get the default instantiator
     *
     * @return InstantiatorInterface
     */
    public function getDefaultInstantiator()
    {
        if ($this->defaultInstantiator === null) {
            $this->defaultInstantiator = new Instantiator();
        }

        return $this->defaultInstantiator;
    }
}
