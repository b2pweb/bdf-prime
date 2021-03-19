<?php

namespace Bdf\Prime\IdGenerators;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\ServiceLocator;

/**
 * GeneratorInterface
 *
 * @template C as ConnectionInterface
 */
interface GeneratorInterface
{
    /**
     * Set the current connection
     * 
     * @param C $connection
     */
    public function setCurrentConnection(ConnectionInterface $connection);

    /**
     * Generate ID
     *
     * @param array $data  By reference
     * @param ServiceLocator   $serviceLocator
     *
     * @throws PrimeException
     */
    public function generate(array &$data, ServiceLocator $serviceLocator);

    /**
     * Modify entity after insertion
     * 
     * @param object $entity
     *
     * @throws PrimeException
     */
    public function postProcess($entity);
}
