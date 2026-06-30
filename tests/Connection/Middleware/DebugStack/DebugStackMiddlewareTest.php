<?php

namespace Connection\Middleware\DebugStack;

use Bdf\Prime\Configuration;
use Bdf\Prime\Connection\Configuration\ConfigurationResolver;
use Bdf\Prime\Connection\ConnectionRegistry;
use Bdf\Prime\Connection\Factory\ChainFactory;
use Bdf\Prime\Connection\Factory\ConnectionFactory;
use Bdf\Prime\Connection\Middleware\DebugStack\DebugStack;
use Bdf\Prime\Connection\Middleware\DebugStack\DebugStackMiddleware;
use Bdf\Prime\Connection\SimpleConnection;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\ServiceLocator;
use PHPUnit\Framework\TestCase;

class DebugStackMiddlewareTest extends TestCase
{
    private DebugStack $debugStack;
    private SimpleConnection $connection;

    protected function setUp(): void
    {
        $registry = new ConnectionRegistry(
            [
                'test' => [
                    'adapter' => 'sqlite',
                    'memory' => true,
                ],
            ],
            new ChainFactory([new ConnectionFactory()]),
            new ConfigurationResolver([], $configuration = new Configuration())
        );

        $configuration->setMiddlewares([
            new DebugStackMiddleware($this->debugStack = new DebugStack()),
        ]);

        $prime = new ServiceLocator(new ConnectionManager($registry));
        $this->connection = $prime->connection('test');
    }

    public function test_query_is_pushed_in_debug_stack(): void
    {
        $this->connection->executeQuery('SELECT 1');

        $this->assertCount(1, $this->debugStack->queries);
        $this->assertSame('SELECT 1', $this->debugStack->queries[0]->sql);
        $this->assertSame([], $this->debugStack->queries[0]->parameters);
    }

    public function test_statement_is_pushed_in_debug_stack(): void
    {
        $this->connection->executeStatement('SELECT 1');

        $this->assertCount(1, $this->debugStack->queries);
        $this->assertSame('SELECT 1', $this->debugStack->queries[0]->sql);
        $this->assertSame([], $this->debugStack->queries[0]->parameters);
    }

    public function test_prepared_statement_parameters_are_pushed_in_debug_stack(): void
    {
        $statement = $this->connection->prepare('SELECT ?');
        $statement->bindValue(1, 42);
        $statement->executeQuery();

        $this->assertCount(1, $this->debugStack->queries);
        $this->assertSame('SELECT ?', $this->debugStack->queries[0]->sql);
        $this->assertSame([42], $this->debugStack->queries[0]->parameters);
    }
}
