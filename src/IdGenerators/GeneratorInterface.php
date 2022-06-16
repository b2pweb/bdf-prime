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
     *
     * @return void
     */
    public function setCurrentConnection(ConnectionInterface $connection): void;

    /**
     * Generate ID
     *
     * The generated id should be filled into $data and not returned
     *
     * @param array $data  By reference
     * @param ServiceLocator   $serviceLocator
     *
     * @return void
     *
     * @throws PrimeException
     */
    public function generate(array &$data, ServiceLocator $serviceLocator): void;

    /**
     * Modify entity after insertion
     *
     * @param object $entity
     *
     * @return void
     *
     * @throws PrimeException
     */
    public function postProcess($entity): void;
}
