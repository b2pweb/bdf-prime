<?php

namespace Bdf\Prime\IdGenerators;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\ServiceLocator;

/**
 * GeneratorInterface
 */
interface GeneratorInterface
{
    /**
     * Set the current connection
     * 
     * @param ConnectionInterface $connection
     */
    public function setCurrentConnection(ConnectionInterface $connection);
    
    /**
     * Generate ID
     *
     * @param array $data  By reference
     * @param ServiceLocator   $serviceLocator
     */
    public function generate(array &$data, ServiceLocator $serviceLocator);
    
    /**
     * Modify entity after insertion
     * 
     * @param object $entity
     */
    public function postProcess($entity);
}
