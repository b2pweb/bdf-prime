<?php

namespace Bdf\Prime;

use Bdf\Prime\Logger\PsrDecorator;
use Bdf\Prime\Platform\PlatformTypeInterface;
use Bdf\Prime\Types\TypesRegistry;
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
