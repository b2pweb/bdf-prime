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
     */
    public function instantiate($className, $hint = null)
    {
        if ($hint === self::USE_CONSTRUCTOR_HINT) {
            return new $className;
        }

        $object = $this->instantiator->instantiate($className);

        if ($object instanceof InitializableInterface) {
            $object->initialize();
        }

        return $object;
    }
}
