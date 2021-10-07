<?php

namespace Bdf\Prime\IdGenerators;

use Bdf\Prime\ServiceLocator;
use Ramsey\Uuid\Uuid;

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
        return $data[$property] = Uuid::uuid4()->toString();
    }
}
