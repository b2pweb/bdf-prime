<?php

namespace Bdf\Prime;

use Bdf\Prime\Connection\Middleware\ConfigurationAwareMiddlewareInterface;
use Bdf\Prime\Logger\PsrDecorator;
use Bdf\Prime\Platform\PlatformTypeInterface;
use Bdf\Prime\Types\TypesRegistry;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 *
 */
class ConfigurationTest extends TestCase
{
    /**
     * 
     */
    public function test_default_values()
    {
        $configuration = new Configuration();
        
        $this->assertEquals(new TypesRegistry(), $configuration->getTypes());
        $this->assertNull($configuration->getSQLLogger());
    }

    /**
     *
     */
    public function test_name()
    {
        $configuration = new Configuration();

        $this->assertNull($configuration->getName());

        $withName = $configuration->withName('foo');

        $this->assertNotSame($configuration, $withName);
        $this->assertNull($configuration->getName());
        $this->assertSame('foo', $withName->getName());

        $this->assertSame($withName, $withName->withName('foo'));
        $this->assertNotSame($withName, $withName->withName('bar'));
    }

    public function test_getMiddleware_with_ConfigurationAwareMiddlewareInterface()
    {
        $configuration = new Configuration();
        $simpleMiddleware = $this->createMock(Middleware::class);
        $configurableMiddleware = new class implements ConfigurationAwareMiddlewareInterface {
            public $config;

            public function wrap(Driver $driver): Driver
            {
                return $driver;
            }

            public function withConfiguration(Configuration $configuration): self
            {
                $self = clone $this;
                $self->config = $configuration;

                return $self;
            }
        };

        $configuration->setMiddlewares([$simpleMiddleware, $configurableMiddleware]);
        $middlewares = $configuration->getMiddlewares();

        $this->assertSame($simpleMiddleware, $middlewares[0]);
        $this->assertNotSame($configurableMiddleware, $middlewares[1]);
        $this->assertSame($configuration, $middlewares[1]->config);
    }

    /**
     *
     */
    public function test_set_parameters_from_constructor()
    {
        $configuration = new Configuration([
            'logger' => $logger = new PsrDecorator(new NullLogger()),
            'autoCommit' => false,
        ]);
        
        $this->assertSame($logger, $configuration->getSQLLogger());
        $this->assertFalse($configuration->getAutoCommit());
    }

    /**
     *
     */
    public function test_set_get_types()
    {
        $types = new TypesRegistry();

        $configuration = new Configuration();
        $configuration->setTypes($types);

        $this->assertSame($types, $configuration->getTypes());
    }

    /**
     *
     */
    public function test_platform_types()
    {
        $t1 = $this->createMock(PlatformTypeInterface::class);
        $t2 = $this->createMock(PlatformTypeInterface::class);

        $configuration = new Configuration();
        $configuration->addPlatformType($t1);
        $configuration->addPlatformType($t2, 'foo');

        $this->assertSame([$t1, 'foo' => $t2], $configuration->getPlatformTypes());
    }
}
