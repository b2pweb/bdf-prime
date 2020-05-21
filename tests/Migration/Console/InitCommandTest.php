<?php

namespace Bdf\Prime\Migration\Console;

use Symfony\Component\Filesystem\Filesystem;

require_once __DIR__ . '/../_assets/CommandTestCase.php';

/**
 *
 */
class InitCommandTest extends CommandTestCase
{
    /**
     * @var string
     */
    protected $workingDir = __DIR__.'/../_working';

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
        $this->filesystem->remove($this->workingDir);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->filesystem->remove($this->workingDir);
    }

    /**
     *
     */
    public function test_execute()
    {
        $output = $this->execute(new InitCommand($this->manager));

        $this->assertTrue(is_dir($this->workingDir));
        $this->assertEquals('Place your migration files in ' . str_replace(getcwd(), '.', realpath($this->workingDir)) . "\n", $output);
    }
}