<?php

namespace Bdf\Prime\Console;

use Bdf\Prime\ServiceLocator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CreateDatabaseCommandTest extends TestCase
{
    /** @var ServiceLocator */
    protected $prime;
    /** @var CreateDatabaseCommand */
    protected $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->command = new CreateDatabaseCommand($this->prime = new ServiceLocator());
    }

    /**
     *
     */
    public function test_ignore()
    {
        $this->prime->connections()->config()->getDbConfig()->set('test', [
            'url' => 'sqlite::memory:',
            'ignore' => '1'
        ]);

        $tester = new CommandTester($this->command);
        $tester->execute([]);

        $this->assertStringContainsString('Connection test is ignored.', $tester->getDisplay());
    }
}
