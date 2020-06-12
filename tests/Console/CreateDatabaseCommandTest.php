<?php

namespace Bdf\Prime\Console;

use Bdf\Prime\Connection\ConnectionRegistry;
use Bdf\Prime\Connection\Factory\ConnectionFactory;
use Bdf\Prime\ServiceLocator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CreateDatabaseCommandTest extends TestCase
{
    /** @var ConnectionRegistry */
    protected $registry;
    /** @var CreateDatabaseCommand */
    protected $command;

    protected function setUp(): void
    {
        parent::setUp();

        $factory = new ConnectionFactory();
        $this->registry = new ConnectionRegistry([], $factory);
        $this->command = new CreateDatabaseCommand($this->registry, $factory);
    }

    /**
     *
     */
    public function test_ignore()
    {
        $this->registry->declareConnection('test', [
            'url' => 'sqlite::memory:',
            'ignore' => '1'
        ]);

        $tester = new CommandTester($this->command);
        $tester->execute([]);

        $this->assertStringContainsString('Connection test is ignored.', $tester->getDisplay());
    }

    /**
     *
     */
    public function test_legacy_constructor()
    {
        $this->command = new CreateDatabaseCommand($prime = new ServiceLocator());
        $prime->config()->getDbConfig()->set('test', [
            'url' => 'sqlite::memory:',
            'ignore' => '1'
        ]);

        $tester = new CommandTester($this->command);
        $tester->execute([]);

        $this->assertStringContainsString('Connection test is ignored.', $tester->getDisplay());
    }
}
