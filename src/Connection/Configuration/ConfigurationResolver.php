<?php

namespace Bdf\Prime\Connection\Configuration;

use Bdf\Prime\Configuration;

class ConfigurationResolver implements ConfigurationResolverInterface
{
    /**
     * @var Configuration[]
     */
    private $configurations;

    /**
     * @var Configuration
     */
    private $default;

    /**
     * ConfigurationResolver constructor.
     *
     * @param Configuration[]|null $configurations
     * @param Configuration|null $default
     */
    public function __construct(array $configurations = null, Configuration $default = null)
    {
        $this->configurations = $configurations;
        $this->default = $default;
    }

    /**
     * Get the configuration of the connection
     *
     * @param string $connectionName
     *
     * @return Configuration
     */
    public function getConfiguration(string $connectionName): ?Configuration
    {
        if (isset($this->configurations[$connectionName])) {
            return $this->configurations[$connectionName];
        }

        return $this->default;
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