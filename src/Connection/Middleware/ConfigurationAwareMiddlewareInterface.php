<?php

namespace Bdf\Prime\Connection\Middleware;

use Bdf\Prime\Configuration;
use Doctrine\DBAL\Driver\Middleware;

/**
 * Middleware which need the configuration instance
 *
 * Note: Implementations of this interface should work even if the configuration is not set
 */
interface ConfigurationAwareMiddlewareInterface extends Middleware
{
    /**
     * Define the configuration instance on the middleware
     * A new instance of the middleware will be returned
     *
     * @param Configuration $configuration
     *
     * @return static The new middleware instance
     */
    public function withConfiguration(Configuration $configuration): self;
}
