<?php

namespace Bdf\Prime\Connection\Configuration;

use Bdf\Prime\Configuration;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ConfigurationResolverTest extends TestCase
{
    /**
     * 
     */
    public function test_default_config()
    {
        $configuration = new Configuration();
        $resolver = new ConfigurationResolver(null, $configuration);

        $this->assertSame($configuration, $resolver->getConfiguration('unknown'));
        $this->assertSame($configuration, $resolver->getConfiguration('unknown'));
    }

    /**
     *
     */
    public function test_custom_config()
    {
        $configuration = new Configuration();
        $foo = new Configuration();
        $bar = new Configuration();
        $resolver = new ConfigurationResolver(['foo' => $foo], $configuration);
        $resolver->addConfiguration('bar', $bar);

        $this->assertSame($configuration, $resolver->getConfiguration('unknown'));
        $this->assertSame($foo, $resolver->getConfiguration('foo'));
        $this->assertSame($bar, $resolver->getConfiguration('bar'));
    }
}