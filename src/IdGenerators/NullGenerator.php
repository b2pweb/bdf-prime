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
    public function generate(array &$data, ServiceLocator $serviceLocator)
    {
        //do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function postProcess($entity)
    {
        //do nothing
    }
}