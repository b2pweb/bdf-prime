<?php

namespace Bdf\Prime\Connection\Factory;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Exception\DBALException;
use Doctrine\DBAL\Configuration;

/**
 * Interface ConnectionFactoryInterface
 */
interface ConnectionFactoryInterface
{
    /**
     * Create a connection instance
     *
     * @param string $connectionName
     * @param array $parameters
     * @param Configuration $config
     *
     * @return ConnectionInterface
     * @throws DBALException
     */
    public function create(string $connectionName, array $parameters, Configuration $config): ConnectionInterface;

    /**
     * Check whether this factory can create the requested connection
     *
     * @param string $connectionName
     * @param array $parameters
     *
     * @return bool
     */
    public function support(string $connectionName, array $parameters): bool;
}
