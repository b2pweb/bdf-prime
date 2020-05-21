<?php

namespace Bdf\Prime\DataCollector;

use Bdf\Prime\ServiceLocator;
use Doctrine\DBAL\Logging\LoggerChain;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 */
class PrimeDataCollectorTest extends TestCase
{
    /**
     *
     */
    public function test_basic()
    {
        $prime = new ServiceLocator();
        $collector = new PrimeDataCollector($prime);

        $this->assertEquals('prime', $collector->getName());
        $this->assertEquals([], $collector->getConnections());
        $this->assertEquals([], $collector->getRepositories());
        $this->assertEquals([], $collector->getQueries());
        $this->assertEquals(0, $collector->getTime());
    }

    /**
     *
     */
    public function test_logger_configuration()
    {
        $prime = new ServiceLocator();
        $collector = new PrimeDataCollector($prime);

        $prime->config()->getSQLLogger();
        $this->assertInstanceOf(LoggerChain::class, $prime->config()->getSQLLogger());
    }

    /**
     *
     */
    public function test_empty_collect()
    {
        $prime = new ServiceLocator();

        $collector = new PrimeDataCollector($prime);
        $collector->collect(new Request(), new Response());

        $this->assertEquals([], $collector->getConnections());
        $this->assertEquals([], $collector->getRepositories());
        $this->assertEquals([], $collector->getQueries());
        $this->assertEquals(0, $collector->getTime());
    }

    /**
     *
     */
    public function test_collect()
    {
        $prime = new ServiceLocator();
        $prime->connections()->addConnection('test', ['adapter' => 'sqlite', 'memory' => true]);
        $collector = new PrimeDataCollector($prime);

        $prime->connection('test')->select('SELECT 1');

        $collector->collect(new Request(), new Response());

        $this->assertEquals(['test'], $collector->getConnections());
        $this->assertEquals([], $collector->getRepositories());
        $this->assertEquals('SELECT 1', $collector->getQueries()[1]['sql']);
        $this->assertTrue($collector->getTime() > 0);
    }

    /**
     *
     */
    public function test_reset()
    {
        $prime = new ServiceLocator();
        $prime->connections()->addConnection('test', ['adapter' => 'sqlite', 'memory' => true]);
        $collector = new PrimeDataCollector($prime);

        $prime->connection('test')->select('SELECT 1');

        $collector->collect(new Request(), new Response());
        $collector->reset();

        $this->assertEquals([], $collector->getConnections());
        $this->assertEquals([], $collector->getRepositories());
        $this->assertEquals([], $collector->getQueries());
        $this->assertEquals(0, $collector->getTime());
    }
}