<?php

namespace Bdf\Prime\Connection\Factory;

use Bdf\Prime\Configuration;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\MasterSlaveConnection;

/**
 * MasterSlaveConnectionLoader
 */
class MasterSlaveConnectionFactory implements ConnectionFactoryInterface
{
    /**
     * The delegated connectionFactory
     *
     * @var ConnectionFactoryInterface
     */
    private $connectionFactory;

    /**
     * Set default configuration
     *
     * @param ConnectionFactoryInterface $connectionFactory
     */
    public function __construct(ConnectionFactoryInterface $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function create(string $connectionName, array $parameters, ?Configuration $config = null): ConnectionInterface
    {
        $masterParameters = $parameters;
        unset($masterParameters['read']);
        unset($masterParameters['wrapperClass']);
        $readParameters = array_merge($masterParameters, $parameters['read']);

        $parameters['read'] = $this->connectionFactory->create($connectionName.'.read', $readParameters, $config);
        $parameters['wrapperClass'] = $parameters['wrapperClass'] ?? MasterSlaveConnection::class;

        return $this->connectionFactory->create($connectionName, $parameters, $config);
    }

    /**
     * {@inheritDoc}
     */
    public function support(string $connectionName, array $parameters): bool
    {
        return isset($parameters['read']);
    }
}
