<?php

namespace Bdf\Prime\Console\ConnectionProvider;

use Bdf\Prime\ConnectionManager;
use Bdf\Prime\Exception\DBALException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Connection as DoctrineConnection;
use Doctrine\DBAL\Tools\Console\ConnectionNotFound;
use Doctrine\DBAL\Tools\Console\ConnectionProvider;

/**
 * The prime adapter for doctrine connection provider used by doctrine in console command
 */
class DoctrineConnectionProviderAdapter implements ConnectionProvider
{
    /**
     * @var ConnectionManager
     */
    private $connectionManager;

    /**
     * @param ConnectionManager $connectionManager
     */
    public function __construct(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultConnection(): Connection
    {
        return $this->getConnection($this->connectionManager->getDefaultConnection());
    }

    /**
     * {@inheritDoc}
     */
    public function getConnection(string $name): Connection
    {
        try {
            $connection = $this->connectionManager->getConnection($name);
        } catch (DBALException $exception) {
            throw new ConnectionNotFound("The connection '$name' is not found.", 0, $exception);
        }

        if (!$connection instanceof DoctrineConnection) {
            throw new ConnectionNotFound("The connection '$name' is not a doctrine connection.");
        }

        return $connection;
    }
}