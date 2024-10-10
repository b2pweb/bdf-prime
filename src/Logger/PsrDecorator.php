<?php

namespace Bdf\Prime\Logger;

use Bdf\Prime\Connection\ConnectionAwareInterface;
use Bdf\Prime\Connection\ConnectionInterface;
use Doctrine\DBAL\Logging\SQLLogger;
use PDO;
use Psr\Log\LoggerInterface;

/**
 * PsrDecorator
 */
class PsrDecorator implements SQLLogger, ConnectionAwareInterface
{
    /**
     * PSR logger
     *
     * @var LoggerInterface |null
     */
    private $logger;

    /**
     * Connection name
     *
     * @var string|null
     */
    private $connectionName;

    /**
     * @param LoggerInterface|null $logger
     */
    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function setConnection(ConnectionInterface $connection): void
    {
        $this->connectionName = $connection->getName();
    }

    /**
     * SQLLogger::startQuery
     *
     * {@inheritdoc}
     */
    public function startQuery($sql, ?array $params = null, ?array $types = null)
    {
        if ($this->logger === null) {
            return;
        }

        if ($this->connectionName !== null) {
            $sql = '['.$this->connectionName.'] '.$sql;
        }

        if ($params) {
            $sql .= $this->getFlattenParams($params, $types);
        }

        $this->logger->debug($sql);
    }

    /**
     * SQLLogger::stopQuery
     *
     * {@inheritdoc}
     */
    public function stopQuery()
    {
    }

    /**
     * Get the type name of the type
     *
     * @param mixed $type
     * @param array $params
     * @param (\Doctrine\DBAL\Types\Type|int|null|string)[]|null $types
     *
     * @return string
     */
    protected function getFlattenParams(array $params, ?array $types)
    {
        $buffer = [];

        foreach ($params as $key => $param) {
            $buffer[] = sprintf(
                '%s => (%s) %s',
                $key,
                isset($types[$key]) ? $this->getTypeName($types[$key]) : '',
                str_replace(PHP_EOL, '', var_export($param, true))
            );
        }

        return ' : [' . implode(', ', $buffer) . ']';
    }

    /**
     * Get the type name of the type
     *
     * @param mixed $type
     * @return string
     */
    protected function getTypeName($type)
    {
        if (is_int($type)) {
            switch ($type) {
                case PDO::PARAM_STR:
                    return 'PDOString';

                case PDO::PARAM_INT:
                    return 'PDOInt';

                case PDO::PARAM_BOOL:
                    return 'PDOBool';

                case PDO::PARAM_NULL:
                    return 'PDONull';
            }
        }

        return $type;
    }

    /**
     * Get the delegated logger
     *
     * @return LoggerInterface|null
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Get the connection name
     *
     * @return string|null
     */
    public function getConnectionName()
    {
        return $this->connectionName;
    }
}
