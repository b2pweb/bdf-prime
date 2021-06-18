<?php

namespace Bdf\Prime;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\ConnectionRegistry;
use Bdf\Prime\Connection\SubConnectionManagerInterface;
use Bdf\Prime\Exception\DBALException;
use LogicException;

/**
 * ConnectionManager
 * 
 * doctrine dbal connection registry
 */
class ConnectionManager implements ConnectionRegistryInterface
{
    /**
     * The connection registry
     *
     * @var ConnectionRegistryInterface
     */
    private $registry;

    /**
     * Connections list
     *
     * @var ConnectionInterface[]
     */
    private $connections = [];

    /**
     * Default connection to use
     * 
     * @var string 
     */
    private $defaultConnection;


    /**
     * Set default configuration
     *
     * @param ConnectionRegistryInterface $registry
     */
    public function __construct(ConnectionRegistryInterface $registry = null)
    {
        $this->registry = $registry ?: new ConnectionRegistry();
    }

    /**
     * Add database connection
     *
     * @param ConnectionInterface $connection Unique name for the connection
     * @param boolean             $default    Use this connection as the default? The first connection added is automatically set as the default, even if this flag is false.
     *
     * @throws LogicException if connection exists
     */
    public function addConnection(ConnectionInterface $connection, bool $default = false)
    {
        // Connection name must be unique
        if (isset($this->connections[$connection->getName()])) {
            throw new LogicException('Connection for "'.$connection->getName().'" already exists. Connection name must be unique.');
        }

        // Set as default connection?
        if (true === $default || null === $this->defaultConnection) {
            $this->defaultConnection = $connection->getName();
        }

        $this->connections[$connection->getName()] = $connection;
    }

    /**
     * Remove a connection by its name
     *
     * @param string $name
     */
    public function removeConnection(string $name)
    {
        if (!isset($this->connections[$name])) {
            return;
        }
        
        $this->connections[$name]->close();
        unset($this->connections[$name]);
    }

    /**
     * Get connection by name
     *
     * @param string $name Unique name of the connection to be returned
     * 
     * @return ConnectionInterface
     * 
     * @throws DBALException
     *
     * @deprecated Since 1.1 use getConnection
     */
    public function connection(string $name = null): ConnectionInterface
    {
        return $this->getConnection($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getConnection(string $name = null): ConnectionInterface
    {
        if ($name === null) {
            $name = $this->defaultConnection;
        }

        // Connection name must be unique
        if (!isset($this->connections[$name]) && !$this->loadSubConnection($name)) {
            $this->addConnection($this->registry->getConnection($name));
        }

        return $this->connections[$name];
    }

    /**
     * Associate configuration to connection
     *
     * @param string $connectionName
     * @param string|array $parameters
     */
    public function declareConnection(string $connectionName, $parameters)
    {
        if ($this->registry instanceof ConnectionRegistry) {
            $this->registry->declareConnection($connectionName, $parameters);
        }
    }

    /**
     * Get global config
     *
     * @return Configuration
     *
     * @deprecated Every connection should have its config.
     */
    public function config(): Configuration
    {
        @trigger_error(__METHOD__.' is deprecated since 1.1 and will be removed in 1.2. Every connection should have its config.', E_USER_DEPRECATED);

        if ($this->registry instanceof ConnectionRegistry) {
            return $this->registry->getDefaultConfiguration();
        }

        return new Configuration();
    }

    /**
     * Get all connections
     *
     * @return ConnectionInterface[] Array of connection objects
     */
    public function connections()
    {
        return $this->connections;
    }

    /**
     * {@inheritDoc}
     */
    public function getConnectionNames(): array
    {
        return array_unique(array_merge($this->getCurrentConnectionNames(), $this->registry->getConnectionNames()));
    }
    /**
     * Get the loaded connection name
     *
     * @return string[] Array of connection name
     *
     * @deprecated Since 1.1 use getConnectionNames
     */
    public function connectionNames(): array
    {
        return $this->getConnectionNames();
    }

    /**
     * Gets the name of connections in progress
     */
    public function getCurrentConnectionNames(): array
    {
        return array_keys($this->connections);
    }

    /**
     * Set the default connection name
     *
     * @param string $name
     */
    public function setDefaultConnection($name)
    {
        $this->defaultConnection = $name;
    }

    /**
     * Get the default connection name
     *
     * @return string
     */
    public function getDefaultConnection()
    {
        return $this->defaultConnection;
    }

    /**
     * Try to load a sub connection
     *
     * This method allows connection as "name.otherName".
     * Works only if connection "name" is a SubConnectionManagerInterface.
     *
     * @param string $connectionName
     * 
     * @return bool  The connection has been loaded
     */
    private function loadSubConnection($connectionName)
    {
        $names = explode('.', $connectionName, 2);

        if (!isset($names[1])) {
            return false;
        }

        $connection = $this->getConnection($names[0]);

        if ($connection instanceof SubConnectionManagerInterface) {
            //TODO doit on concerver une reference sur la sous connection ?
            $this->connections[$connectionName] = $connection->getConnection($names[1]);
            return true;
        }

        return false;
    }
}
