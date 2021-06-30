<?php

namespace Bdf\Prime\Connection\Factory;

use Bdf\Prime\Configuration;
use Bdf\Prime\Connection\ConnectionInterface;

/**
 * ChainFactory
 */
class ChainFactory implements ConnectionFactoryInterface
{
    /**
     * The connection factories
     *
     * @var ConnectionFactoryInterface[]
     */
    private $factories;

    public function __construct(array $connectionFactories)
    {
        $this->factories = $connectionFactories;
    }

    /**
     * {@inheritDoc}
     */
    public function create(string $connectionName, array $parameters, Configuration $config = null): ConnectionInterface
    {
        foreach ($this->factories as $connectionFactory) {
            if ($connectionFactory->support($connectionName, $parameters)) {
                return $connectionFactory->create($connectionName, $parameters, $config);
            }
        }

        throw new \LogicException('No handlers found to create the connection '.$connectionName);
    }

    /**
     * {@inheritDoc}
     */
    public function support(string $connectionName, array $parameters): bool
    {
        return true;
    }
}
