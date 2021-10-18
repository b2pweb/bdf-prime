<?php

namespace Bdf\Prime\IdGenerators;

use Bdf\Prime\ServiceLocator;

/**
 * NullGenerator
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