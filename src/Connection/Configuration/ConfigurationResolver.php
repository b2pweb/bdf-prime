<?php

namespace Bdf\Prime\Connection\Configuration;

use Bdf\Prime\Configuration;

/**
 * Allows declaration of configuration custom by connection. Use a default connection if the configuration is not set.
 */
class ConfigurationResolver implements ConfigurationResolverInterface
{
    /**
     * @var Configuration[]
     */
    private $configurations;

    /**
     * @var Configuration|null
     */
    private $default;

    /**
     * ConfigurationResolver constructor.
     *
     * @param Configuration[] $configurations
     * @param Configuration|null $default
     */
    public function __construct(array $configurations = [], ?Configuration $default = null)
    {
        $this->configurations = $configurations;
        $this->default = $default;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfiguration(string $connectionName): ?Configuration
    {
        return $this->configurations[$connectionName] ?? $this->default;
    }

    /**
     * Declare a configuration of a connection
     *
     * @param string $connectionName
     * @param Configuration $configuration
     */
    public function addConfiguration(string $connectionName, Configuration $configuration): void
    {
        $this->configurations[$connectionName] = $configuration;
    }
}