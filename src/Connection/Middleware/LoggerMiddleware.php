<?php

namespace Bdf\Prime\Connection\Middleware;

use Bdf\Prime\Configuration;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Logging\Driver as LoggingDriver;
use Doctrine\DBAL\Logging\Middleware;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

use function str_replace;

/**
 * Middleware for add logging on all executed queries
 *
 * Unlike {@see Middleware}, this middleware will add the connection name to the log context and message
 */
final class LoggerMiddleware implements ConfigurationAwareMiddlewareInterface
{
    private LoggerInterface $logger;
    private ?Configuration $configuration = null;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function withConfiguration(Configuration $configuration): self
    {
        $self = clone $this;
        $self->configuration = $configuration;

        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public function wrap(Driver $driver): Driver
    {
        $logger = $this->logger;
        $connectionName = $this->configuration ? $this->configuration->getName() : null;

        if ($connectionName) {
            $logger = new class ($logger, $connectionName) extends AbstractLogger {
                private LoggerInterface $logger;
                private string $connectionName;

                public function __construct(LoggerInterface $logger, string $connectionName)
                {
                    $this->logger = $logger;
                    $this->connectionName = $connectionName;
                }

                public function log($level, $message, array $context = []): void
                {
                    $this->logger->log((string) $level, $this->format((string) $message, $context), ['connection' => $this->connectionName] + $context);
                }

                /**
                 * Format the message by replacing {placeholder}, and adding the connection name
                 */
                private function format(string $message, array $context): string
                {
                    foreach ($context as $key => $value) {
                        $value = is_scalar($value) ? $value : json_encode($value);
                        $message = str_replace('{'.$key.'}', (string) $value, $message);
                    }

                    return "[{$this->connectionName}] {$message}";
                }
            };
        }

        /** @psalm-suppress InternalMethod */
        return new LoggingDriver($driver, $logger);
    }
}
