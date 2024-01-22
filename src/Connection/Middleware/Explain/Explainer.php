<?php

namespace Bdf\Prime\Connection\Middleware\Explain;

use Bdf\Prime\Connection\Middleware\Explain\Platform\ExplainPlatformInterface;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\ParameterType;
use Throwable;

/**
 * Facade class for perform explain query
 */
final class Explainer
{
    private Connection $connection;
    private ExplainPlatformInterface $platform;

    public function __construct(Connection $connection, ExplainPlatformInterface $platform)
    {
        $this->connection = $connection;
        $this->platform = $platform;
    }

    /**
     * Explain a query
     * If the explain is not supported by the platform, or if it fails, the method returns null
     *
     * @param string $query The SQL query to explain
     * @param array $parameters The query parameters
     * @param array $types The query parameters types. If not provided, all parameters are considered as strings
     *
     * @return ExplainResult|null The explain result, or null if the query cannot be explained
     */
    public function explain(string $query, array $parameters = [], array $types = []): ?ExplainResult
    {
        $explainQuery = $this->platform->compile($query);

        if (!$explainQuery) {
            return null;
        }

        try {
            if (!$parameters) {
                $result = $this->connection->query($explainQuery);
            } else {
                $stmt = $this->connection->prepare($explainQuery);

                foreach ($parameters as $key => $value) {
                    $stmt->bindValue($key, $value, $types[$key] ?? ParameterType::STRING);
                }

                $result = $stmt->execute();
            }
        } catch (Throwable $e) {
            return null;
        }

        return $this->platform->parse($result);
    }
}
