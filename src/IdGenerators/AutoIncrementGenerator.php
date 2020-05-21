<?php

namespace Bdf\Prime\IdGenerators;

use Bdf\Prime\ServiceLocator;

/**
 * AutoIncrementGenerator
 */
class AutoIncrementGenerator extends AbstractGenerator
{
    /**
     * {@inheritdoc}
     */
    protected function doGenerate($property, array &$data, ServiceLocator $serviceLocator)
    {
        unset($data[$property]);
    }

    /**
     * {@inheritdoc}
     */
    protected function lastGeneratedId()
    {
        return $this->connection()->lastInsertId();
    }
}