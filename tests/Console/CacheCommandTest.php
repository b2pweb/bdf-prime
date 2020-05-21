<?php

namespace Bdf\Prime\Console;

use Bdf\Prime\Cache\CacheInterface;
use Bdf\Prime\ServiceLocator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group Bdf_Prime
 * @group Bdf_Prime_Console
 */
class CacheCommandTest extends TestCase
{
    /**
     * @var ServiceLocator
     */
    protected $prime;
    protected $command;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->command = new CacheCommand($this->prime = new ServiceLocator());
    }
    
    /**
     * 
     */
    public function test_without_cache()
    {
        $tester = new CommandTester($this->command);
        $tester->execute([]);
        $display = $tester->getDisplay();
        
        $this->assertRegExp('/result cache is not available/', $display);
        $this->assertRegExp('/metadata cache is not available/', $display);
    }

    /**
     *
     */
    public function test_cache_without_option()
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->never())->method('clear');

        $this->prime->config()->setResultCache($cache);

        $tester = new CommandTester($this->command);
        $tester->execute([]);

        $this->assertRegExp('/nothing to do/', $tester->getDisplay());
    }

    /**
     *
     */
    public function test_cache()
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())->method('clear');

        $this->prime->config()->setResultCache($cache);

        $tester = new CommandTester($this->command);
        $tester->execute(['--clear' => true]);

        $this->assertRegExp('/Clearing result cache/', $tester->getDisplay());
    }
}