<?php

namespace Bdf\Prime\IdGenerators;

use Bdf\Prime\ServiceLocator;

/**
 * AutoIncrementGenerator
 *
 * @extends AbstractGenerator<\Bdf\Prime\Connection\ConnectionInterface&\Doctrine\DBAL\Connection>
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
        return (string) $this->connection()->lastInsertId();
    }
}
