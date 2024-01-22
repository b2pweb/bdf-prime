<?php

namespace Bdf\Prime\Connection\Middleware\Explain;

use Bdf\Prime\Connection\Middleware\Explain\Platform\ExplainPlatformFactory;
use Bdf\Prime\Connection\Middleware\Explain\Report\ExplainReporter;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;

/**
 * Middleware for execute explain query on each query
 */
final class ExplainMiddleware implements Driver\Middleware
{
    private ExplainReporter $reporter;
    private ExplainPlatformFactory $platformFactory;

    public function __construct(ExplainReporter $reporter, ?ExplainPlatformFactory $platformFactory = null)
    {
        $this->reporter = $reporter;
        $this->platformFactory = $platformFactory ?? new ExplainPlatformFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function wrap(Driver $driver): Driver
    {
        return new class($driver, $this->reporter, $this->platformFactory) extends AbstractDriverMiddleware {
            private ExplainReporter $reporter;
            private ExplainPlatformFactory $platformFactory;

            public function __construct(Driver $wrappedDriver, ExplainReporter $reporter, ExplainPlatformFactory $platformFactory)
            {
                parent::__construct($wrappedDriver);

                $this->reporter = $reporter;
                $this->platformFactory = $platformFactory;
            }

            public function connect(array $params): Connection
            {
                $connection = parent::connect($params);

                return new ExplainMiddlewareConnection(
                    $connection,
                    new Explainer(
                        $connection,
                        $this->platformFactory->createExplainPlatform($this->getDatabasePlatform())
                    ),
                    $this->reporter
                );
            }
        };
    }
}
