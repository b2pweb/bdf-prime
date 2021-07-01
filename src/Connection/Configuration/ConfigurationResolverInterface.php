<?php

namespace Bdf\Prime\Connection\Configuration;

use Bdf\Prime\Configuration;

interface ConfigurationResolverInterface
{
    /**
     * Get the configuration of the connection
     *
     * @param string $connectionName
     *
     * @return Configuration|null
     */
    public function getConfiguration(string $connectionName): ?Configuration;
}