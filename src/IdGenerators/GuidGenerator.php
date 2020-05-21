<?php

namespace Bdf\Prime\IdGenerators;

use Bdf\Prime\ServiceLocator;

/**
 * GUID generator
 * 
 * generate guid from UUID expression
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

        return $data[$property] = $stmt->fetchColumn(0);
    }
}