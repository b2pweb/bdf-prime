<?php

namespace Connection\Middleware;

use Bdf\Prime\Configuration;
use Bdf\Prime\Connection\Configuration\ConfigurationResolver;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\ConnectionRegistry;
use Bdf\Prime\Connection\Factory\ChainFactory;
use Bdf\Prime\Connection\Factory\ConnectionFactory;
use Bdf\Prime\Connection\Factory\MasterSlaveConnectionFactory;
use Bdf\Prime\Connection\Factory\ShardingConnectionFactory;
use Bdf\Prime\Connection\Middleware\LoggerMiddleware;
use Bdf\Prime\Connection\SimpleConnection;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\ServiceLocator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LoggerMiddlewareTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    private $logger;
    private SimpleConnection $connection;

    protected function setUp(): void
    {
        $registry = new ConnectionRegistry(
            [
                'test' => [
                    'adapter' => 'sqlite',
                    'memory' => true
                ],
            ],
            new ChainFactory([new ConnectionFactory()]),
            new ConfigurationResolver([], $configuration = new Configuration())
        );

        $configuration
            ->setMiddlewares([
                new LoggerMiddleware($this->logger = $this->createMock(LoggerInterface::class))
            ])
        ;

        $prime = new ServiceLocator(new ConnectionManager($registry));

        $this->connection = $prime->connection('test');
    }

    public function test_query()
    {
        $this->logger->expects($this->exactly(3))->method('log')->withConsecutive(
            [LogLevel::INFO, '[test] Connecting with parameters {"memory":true,"driver":"pdo_sqlite","charset":"utf8","wrapperClass":"Bdf\\\Prime\\\Connection\\\SimpleConnection"}', ['connection' => 'test', 'params' => [
                'memory' => true,
                'driver' => 'pdo_sqlite',
                'charset' => 'utf8',
                'wrapperClass' => 'Bdf\Prime\Connection\SimpleConnection',
            ]]],
            [LogLevel::DEBUG, '[test] Executing query: SELECT 1', ['connection' => 'test', 'sql' => 'SELECT 1']],
            [LogLevel::INFO, '[test] Disconnecting', ['connection' => 'test']],
        );
        $this->connection->executeQuery('SELECT 1');
        $this->connection->close();

        unset($this->connection);
    }

    public function test_prepared_statement()
    {
        $this->logger->expects($this->exactly(4))->method('log')->withConsecutive(
            [LogLevel::INFO, '[test] Connecting with parameters {"memory":true,"driver":"pdo_sqlite","charset":"utf8","wrapperClass":"Bdf\\\Prime\\\Connection\\\SimpleConnection"}', ['connection' => 'test', 'params' => [
                'memory' => true,
                'driver' => 'pdo_sqlite',
                'charset' => 'utf8',
                'wrapperClass' => 'Bdf\Prime\Connection\SimpleConnection',
            ]]],
            [LogLevel::DEBUG, '[test] Executing statement: SELECT ? (parameters: {"1":1}, types: {"1":2})', ['connection' => 'test', 'sql' => 'SELECT ?', 'params' => [1 => 1], 'types' => [1 => 2]]],
            [LogLevel::DEBUG, '[test] Executing statement: SELECT ? (parameters: {"1":2}, types: {"1":2})', ['connection' => 'test', 'sql' => 'SELECT ?', 'params' => [1 => 2], 'types' => [1 => 2]]],
            [LogLevel::INFO, '[test] Disconnecting', ['connection' => 'test']],
        );
        $stmt = $this->connection->prepare('SELECT ?');
        $stmt->bindValue(1, 1);
        $stmt->executeQuery();

        $stmt->bindValue(1, 2);
        $stmt->executeQuery();

        $this->connection->close();

        unset($this->connection);
    }

    public function test_query_object()
    {
        $this->logger->expects($this->exactly(3))->method('log')->withConsecutive(
            [LogLevel::INFO, '[test] Connecting with parameters {"memory":true,"driver":"pdo_sqlite","charset":"utf8","wrapperClass":"Bdf\\\Prime\\\Connection\\\SimpleConnection"}', ['connection' => 'test', 'params' => [
                'memory' => true,
                'driver' => 'pdo_sqlite',
                'charset' => 'utf8',
                'wrapperClass' => 'Bdf\Prime\Connection\SimpleConnection',
            ]]],
            [LogLevel::DEBUG, '[test] Executing query: SELECT 1 FROM (SELECT 1)', ['connection' => 'test', 'sql' => 'SELECT 1 FROM (SELECT 1)']],
            [LogLevel::INFO, '[test] Disconnecting', ['connection' => 'test']],
        );

        $this->connection->builder()
            ->select('1')
            ->from('(SELECT 1)')
            ->execute()
        ;

        $this->connection->close();

        unset($this->connection);
    }
}
