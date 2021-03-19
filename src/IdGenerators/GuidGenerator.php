<?php

namespace Bdf\Prime\IdGenerators;

use Bdf\Prime\ServiceLocator;

/**
 * GUID generator
 * 
 * generate guid from UUID expression
 *
 * @extends AbstractGenerator<\Bdf\Prime\Connection\ConnectionInterface&\Doctrine\DBAL\Connection>
 */
class GuidGenerator extends AbstractGenerator
{
    /**
     * {@inheritdoc}
     */
    protected function doGenerate($property, array &$data, ServiceLocator $serviceLocator)
    {
        $connection = $this->connection();
        $grammar = $connection->platform()->grammar();

        $stmt = $connection->query('SELECT '.$grammar->getGuidExpression());

        return $data[$property] = (string) $stmt->fetchColumn(0);
    }
}
