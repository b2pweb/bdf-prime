<?php

namespace Bdf\Prime\Entity\Instantiator;

use Doctrine\Instantiator\InstantiatorInterface as BaseInstantiatorInterface;

/**
 * Instantiator interface
 */
interface InstantiatorInterface extends BaseInstantiatorInterface
{
    const USE_CONSTRUCTOR_HINT = 1;

    /**
     * {@inheritdoc}
     *
     * Instantiate an object from its class name
     *
     * @param class-string<T> $className  The class name to instantiate
     * @param null|int $hint     The instantiation hint flag
     *
     * @return T
     * @template T as object
     */
    public function instantiate($className, $hint = null);
}
