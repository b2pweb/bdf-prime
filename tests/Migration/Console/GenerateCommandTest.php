<?php

namespace Bdf\Prime\Migration\Console;

use Bdf\Prime\Migration\Migration;
use Bdf\Prime\Migration\MigrationManager;
use Bdf\Prime\Migration\Version\DbVersionRepository;
use Bdf\Prime\Migration\VersionRepositoryInterface;
use Bdf\Prime\Prime;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

require_once __DIR__ . '/../_assets/CommandTestCase.php';

/**
 *
 */
class GenerateCommandTest extends CommandTestCase
{
    /**
     * @var string
     */
    protected $workingDir =  __DIR__ . '/../_working';

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
        $this->filesystem->mkdir($this->workingDir);
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
    public function test_execute_when_path_does_not_exists()
    {
        $this->filesystem->remove($this->workingDir);

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The path "' . $this->workingDir . '" is not writable, please consider running the init command first');
        $this->execute(new GenerateCommand($this->manager), ['name' => 'UnitMigration']);
    }

    /**
     *
     */
    public function test_execute_when_migration_already_exists()
    {
        $innerRepository = new DbVersionRepository(Prime::connection('test'), 'migration');
        $repository = new class($innerRepository) implements VersionRepositoryInterface
        {
            public function __construct(
                private DbVersionRepository $inner,
            ) {}

            public function newIdentifier(): string
            {
                return '123';
            }

            public function has(string $version): bool
            {
                return $this->inner->has($version);
            }

            public function current(): string
            {
                return $this->inner->current();
            }

            public function all(): array
            {
                return $this->inner->all();
            }

            public function add(string $version)
            {
                return $this->inner->add($version);
            }

            public function remove(string $version)
            {
                return $this->inner->remove($version);
            }
        };

        $this->repository = $repository;
        $this->manager = new MigrationManager($this->repository, $this->provider);

        try {
            $this->execute(new GenerateCommand($this->manager), ['name' => 'NewMigration']);
            $this->execute(new GenerateCommand($this->manager), ['name' => 'NewMigration']);
            $this->fail('Failed asserting exception');

        } catch (InvalidArgumentException $exception) {
            $this->assertMatchesRegularExpression("/Duplicate migration version/", $exception->getMessage());
        }
    }

    /**
     *
     */
    public function test_execute()
    {
        $output = $this->execute(new GenerateCommand($this->manager), ['name' => 'UnitMigration']);

        $this->assertEquals(1, preg_match("/\\+f [a-zA-Z\\.\\/]+_working\\/([0-9]+)_([a-zA-Z]+)\\.php/", $output, $matches));
        list($match, $version, $class) = $matches;

        $file = $this->workingDir . DIRECTORY_SEPARATOR . $version . '_' . $class . '.php';
        $this->assertTrue($this->filesystem->exists($file));

        require_once $file;
        $this->assertTrue(class_exists($class));

        $migration = new $class($version, $this->createMock(ContainerInterface::class));
        $this->assertInstanceOf('Bdf\Prime\Migration\Migration', $migration);
        $this->assertEquals('UnitMigration', $migration->name());
        $this->assertEquals(Migration::STAGE_DEFAULT, $migration->stage());
    }

    /**
     *
     */
    public function test_execute_custom_stage()
    {
        $output = $this->execute(new GenerateCommand($this->manager), ['name' => 'StagedMigration', '--stage' => 'my_stage']);

        $this->assertEquals(1, preg_match("/\\+f [a-zA-Z\\.\\/]+_working\\/([0-9]+)_([a-zA-Z]+)\\.php/", $output, $matches));
        list($match, $version, $class) = $matches;

        $file = $this->workingDir . DIRECTORY_SEPARATOR . $version . '_' . $class . '.php';
        $this->assertTrue($this->filesystem->exists($file));

        require_once $file;
        $this->assertTrue(class_exists($class));

        $migration = new $class($version, $this->createMock(ContainerInterface::class));
        $this->assertInstanceOf(Migration::class, $migration);
        $this->assertEquals('StagedMigration', $migration->name());
        $this->assertEquals('my_stage', $migration->stage());
    }
}
