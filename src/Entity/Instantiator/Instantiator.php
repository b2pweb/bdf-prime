<?php

namespace Bdf\Prime\Entity\Instantiator;

use Bdf\Prime\Entity\InitializableInterface;
use Doctrine\Instantiator\Instantiator as DoctrineInstantiator;
use Doctrine\Instantiator\InstantiatorInterface as DoctrineInstantiatorInterface;

/**
 * Instantiator
 */
class Instantiator implements InstantiatorInterface
{
    /**
     * @var DoctrineInstantiatorInterface
     */
    protected $instantiator;

    /**
     * Instantiator constructor.
     *
     * @param DoctrineInstantiatorInterface $instantiator
     */
    public function __construct(DoctrineInstantiatorInterface $instantiator = null)
    {
        $this->instantiator = $instantiator ?: new DoctrineInstantiator();
    }

    /**
     * Get the entity instantiator
     *
     * @return DoctrineInstantiatorInterface
     */
    public function instantiator()
    {
        return $this->instantiator;
    }

    /**
     * {@inheritdoc}
     *
     * @param class-string<T> $className  The class name to instantiate
     * @param null|int $hint     The instantiation hint flag
     *
     * @return T
     * @template T as object
     */
    public function instantiate($className, $hint = null)
    {
        if ($hint === self::USE_CONSTRUCTOR_HINT) {
            return new $className;
        }

        /** @var T $object */
        $object = $this->instantiator->instantiate($className);

        if ($object instanceof InitializableInterface) {
            $object->initialize();
        }

        return $object;
    }
}
