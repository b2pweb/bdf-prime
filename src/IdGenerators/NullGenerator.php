<?php

namespace Bdf\Prime\IdGenerators;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\ServiceLocator;

/**
 * NullGenerator
 *
 * @template C as ConnectionInterface
 * @extends AbstractGenerator<C>
 */
class NullGenerator extends AbstractGenerator
{
    /**
     * {@inheritdoc}
     */
    public function generate(array &$data, ServiceLocator $serviceLocator): void
    {
        //do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function postProcess($entity): void
    {
        //do nothing
    }
}
