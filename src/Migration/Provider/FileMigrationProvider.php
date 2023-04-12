<?php

namespace Bdf\Prime\Migration\Provider;

use Bdf\Prime\Migration\MigrationFactoryInterface;
use Bdf\Prime\Migration\MigrationInterface;
use Bdf\Prime\Migration\MigrationProviderInterface;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Migration file provider
 */
class FileMigrationProvider implements MigrationProviderInterface
{
    /**
     * The migration factory
     *
     * @var MigrationFactoryInterface
     */
    private $factory;

    /**
     * The path to migration files
     *
     * @var string
     */
    private $path;

    /**
     * The collection of migration name by version
     *
     * @var MigrationInterface[]
     */
    private $migrations = [];

    /**
     * Locator constructor
     *
     * @param MigrationFactoryInterface $factory
     * @param string $path
     */
    public function __construct(MigrationFactoryInterface $factory, string $path)
    {
        $this->factory = $factory;
        $this->path = $path;
    }

    /**
     *{@inheritDoc}
     */
    public function initRepository(): void
    {
        if (is_dir($this->path)) {
            return;
        }

        (new Filesystem())->mkdir($this->path);
    }

    /**
     *{@inheritDoc}
     *
     * @throws InvalidArgumentException  If path is not writable
     * @throws RuntimeException
     */
    public function create(string $version, string $name, string $stage = MigrationInterface::STAGE_DEFAULT, array $upQueries = [], array $downQueries = []): string
    {
        $name = $this->normalizeName($name);

        // Check if the path is writable
        if (!$this->path || !is_writable($this->path)) {
            throw new InvalidArgumentException(sprintf(
                'The path "%s" is not writable, please consider running the init command first',
                $this->path
            ));
        }

        $this->assertUnique($version, $name);
        $path = $this->createFilename($version, $name);

        if (is_file($path)) {
            throw new InvalidArgumentException(sprintf(
                'The file "%s" already exists',
                $path
            ));
        }

        $content = $stage === MigrationInterface::STAGE_DEFAULT
            ? file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'migration.stub')
            : file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'migration-staged.stub')
        ;

        $up = $this->generateQueryCalls($upQueries);
        $down = $this->generateQueryCalls($downQueries);

        // Try to write the migration file
        if (!file_put_contents($path, str_replace(['{className}', '{version}', '{stage}', '{up}', '{down}'], [$name, $version, $stage, $up, $down], $content))) {
            throw new RuntimeException(sprintf(
                'The file "%s" could not be written to',
                $path
            ));
        }

        return realpath($path);
    }

    /**
     *{@inheritDoc}
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     *{@inheritDoc}
     */
    public function all(): array
    {
        return $this->migrations;
    }

    /**
     *{@inheritDoc}
     *
     * @throws InvalidArgumentException
     */
    public function migration(string $version): MigrationInterface
    {
        if (!$this->has($version)) {
            throw new InvalidArgumentException("Unable to provide Migration for version $version");
        }

        return $this->migrations[$version];
    }

    /**
     *{@inheritDoc}
     */
    public function has(string $version): bool
    {
        return isset($this->migrations[$version]);
    }

    /**
     *{@inheritDoc}
     */
    public function import(): void
    {
        $this->migrations = [];

        $paths = glob(realpath($this->path) . DIRECTORY_SEPARATOR . '*.php');

        foreach ($paths as $path) {
            list($version, $className) = $this->parseFilename($path);

            $this->assertUnique($version, $className);

            if (!class_exists($className)) {
                require_once $path;
            }

            $this->migrations[$version] = $this->factory->create($className, $version);
        }

        ksort($this->migrations, SORT_NUMERIC);
    }

    /**
     * Assert that the version and classname are unique
     *
     * @param string $version
     * @param string $className
     *
     * @throws InvalidArgumentException
     *
     * @return void
     */
    private function assertUnique($version, $className): void
    {
        // Check if version already exists
        if (isset($this->migrations[$version])) {
            throw new InvalidArgumentException(sprintf(
                'Duplicate migration version %s, "%s" has the same version as "%s"',
                $version,
                $className,
                $this->migrations[$version]->name()
            ));
        }

        // Check if migration name already exists
        $definedMigration = array_search($className, array_map(function (MigrationInterface $migration) {
            return $migration->name();
        }, $this->migrations));

        if (false !== $definedMigration) {
            throw new InvalidArgumentException(sprintf(
                'Duplicate migration name "%s", version "%s" has the same name as version "%s"',
                $className,
                $version,
                $definedMigration
            ));
        }
    }

    /**
     * Get version and migration name from a file path
     *
     * @param string $filename
     *
     * @return string[]
     *
     * @throws InvalidArgumentException
     */
    private function parseFilename($filename)
    {
        if (!preg_match('/^([0-9]+)_(.+)\.php/', basename($filename), $matches)) {
            throw new InvalidArgumentException(sprintf('The file "%s" does not have a valid migration filename', $filename));
        }

        return [
            $matches[1],
            $matches[2],
        ];
    }

    /**
     * Get file name from version and migration class name
     *
     * @param string $version
     * @param string $className
     *
     * @return string  The file name
     */
    private function createFilename($version, $className)
    {
        return $this->path . DIRECTORY_SEPARATOR . $version . '_' . $className . '.php';
    }

    /**
     * Normalize the name of the migration
     *
     * @param string $name
     *
     * @return string
     */
    private function normalizeName($name)
    {
        $name = str_replace(['_', '.'], ' ', $name);
        $name = ucwords($name);

        return str_replace(' ', '', $name);
    }

    /**
     * @param array<string, list<string>> $queries Queries indexed by connection name
     * @return string
     */
    private function generateQueryCalls(array $queries): string
    {
        $calls = '';
        foreach ($queries as $connection => $connectionQueries) {
            $connection = var_export($connection, true);

            foreach ($connectionQueries as $query) {
                $query = var_export($query, true);
                $calls .= "\n        \$this->update($query, [], $connection);";
            }
        }

        return $calls;
    }
}
