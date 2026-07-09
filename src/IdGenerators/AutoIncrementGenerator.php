<?php

namespace Bdf\Prime\IdGenerators;

use Bdf\Prime\ServiceLocator;

/**
 * AutoIncrementGenerator
 *
 * @extends AbstractGenerator<\Bdf\Prime\Connection\ConnectionInterface&\Doctrine\DBAL\Connection>
 */
final class AutoIncrementGenerator extends AbstractGenerator
{
    /**
     * {@inheritdoc}
     */
    protected function doGenerate($property, array &$data, ServiceLocator $serviceLocator): string|int|null
    {
        unset($data[$property]);

        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function lastGeneratedId(): string|int|null
    {
        return (string) $this->connection()->lastInsertId();
    }
}
