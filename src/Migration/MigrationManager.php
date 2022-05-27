<?php

namespace Bdf\Prime\Migration;

use Bdf\Prime\Exception\PrimeException;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Migration facade
 *
 * Manage the migration process
 */
class MigrationManager
{
    public const UP = 'up';
    public const DOWN = 'down';

    /**
     * The upgraded migration repository
     *
     * @var VersionRepositoryInterface
     */
    private $repository;

    /**
     * The migration provider
     *
     * @var MigrationProviderInterface
     */
    private $provider;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var HelperSet
     */
    private $helper;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * Constructor
     *
     * @param VersionRepositoryInterface $repository
     * @param MigrationProviderInterface $provider
     */
    public function __construct(VersionRepositoryInterface $repository, MigrationProviderInterface $provider)
    {
        $this->repository = $repository;
        $this->provider = $provider;
        $this->output = new NullOutput();

        $this->provider->import();
    }

    /**
     * Get the migration directory
     *
     * @return string
     */
    public function getMigrationPath(): string
    {
        return $this->provider->path();
    }

    /**
     * Create the migration directory
     */
    public function initMigrationRepository(): void
    {
        $this->provider->initRepository();
    }

    /**
     * Create a migration file
     *
     * @param string $name
     * @param string $stage
     *
     * @return string
     * @throws PrimeException
     */
    public function createMigration(string $name, string $stage): string
    {
        $path = $this->provider->create($this->repository->newIdentifier(), $name, $stage);
        $this->provider->import(); // Refresh migration list

        return $path;
    }

    /**
     * Get the available migrations
     *
     * @param string|null $stage The migration stage, or null to not perform stage filter
     *
     * @return MigrationInterface[]
     */
    public function getMigrations(string $stage = null): array
    {
        $migrations = $this->provider->all();

        if (!$stage) {
            return $migrations;
        }

        return array_filter($migrations, function (MigrationInterface $migration) use ($stage) {
            return $migration->stage() === $stage;
        });
    }

    /**
     * Get an instance of migration
     *
     * @param string $version
     *
     * @return MigrationInterface
     */
    public function getMigration(string $version): MigrationInterface
    {
        return $this->provider->migration($version);
    }

    /**
     * Get awaiting migrations
     *
     * @param string|null $stage The migration stage, or null to not perform stage filter
     *
     * @return MigrationInterface[]
     * @throws PrimeException
     */
    public function getDownMigrations(string $stage = null): array
    {
        $migrations = [];

        foreach ($this->getMigrations($stage) as $version => $name) {
            if (!$this->isUp($version)) {
                $migrations[$version] = $name;
            }
        }

        return $migrations;
    }

    /**
     * Get the upgraded migrations
     *
     * @return array
     * @throws PrimeException
     */
    public function getVersions(): array
    {
        return $this->repository->all();
    }

    /**
     * Get the missing migrations
     *
     * A missing migration is an upgraded migration not available
     *
     * @return string[]
     * @throws PrimeException
     */
    public function getMissingMigrations(): array
    {
        $versions = [];

        foreach ($this->getVersions() as $version) {
            if (!$this->hasMigration($version)) {
                $versions[] = $version;
            }
        }

        return $versions;
    }

    /**
     * Get the last upgraded version
     *
     * @return string
     * @throws PrimeException
     */
    public function getCurrentVersion(): string
    {
        return $this->repository->current();
    }

    /**
     * Get the most recent version found in upgraded and available
     *
     * @param string $stage The migration stage
     *
     * @return string|null
     * @throws PrimeException
     */
    public function getMostRecentVersion(string $stage = MigrationInterface::STAGE_DEFAULT): ?string
    {
        $versions = array_merge($this->getVersions(), $this->getAvailableVersions($stage));

        if (empty($versions)) {
            return null;
        }

        return max($versions);
    }

    /**
     * Get the available version found in directory
     *
     * @param string $stage The migration stage
     *
     * @return array
     */
    public function getAvailableVersions(string $stage = MigrationInterface::STAGE_DEFAULT): array
    {
        return array_keys($this->getMigrations($stage));
    }

    /**
     * Check whether version is older than given version
     *
     * @param string $version
     * @param string $baseVersion
     *
     * @return bool
     * @throws PrimeException
     */
    public function isOlder(string $version, string $baseVersion = null): bool
    {
        $baseVersion = $baseVersion ?: $this->getCurrentVersion();

        return strcmp($version, $baseVersion) < 0;
    }

    /**
     * Check whether the version is available
     *
     * @param string $version
     * @param string|null $stage The migration stage, or null to not check the stage
     *
     * @return boolean
     */
    public function hasMigration(string $version, string $stage = null): bool
    {
        return
            $this->provider->has($version)
            && (!$stage || $this->provider->migration($version)->stage() === $stage)
        ;
    }

    /**
     * Check whether the version was upgraded
     *
     * @param string $version
     *
     * @return boolean
     * @throws PrimeException
     */
    public function isUp(string $version): bool
    {
        return $this->repository->has($version);
    }

    /**
     * Upgrade a migration from its version
     *
     * @param string $version
     * @throws PrimeException
     */
    public function up(string $version): void
    {
        $this->run($this->provider->migration($version), self::UP);
        $this->repository->add($version);
    }

    /**
     * Downgrade a migration from its version
     *
     * @param string $version
     * @throws PrimeException
     */
    public function down(string $version): void
    {
        $this->run($this->provider->migration($version), self::DOWN);
        $this->repository->remove($version);
    }

    /**
     * Upgrade migrations under the entry point
     *
     * @param string $endpoint A version
     * @param string $stage The migration stage
     *
     * @throws PrimeException
     */
    public function migrate(string $endpoint, string $stage = MigrationInterface::STAGE_DEFAULT): void
    {
        $versions = $this->getAvailableVersions($stage);
        sort($versions);

        foreach ($versions as $version) {
            if ($version > $endpoint) {
                break;
            }

            if (!$this->isUp($version)) {
                $this->up($version);
            }
        }
    }

    /**
     * Downgrade migrations sup to the end point
     *
     * @param string $endpoint
     * @param string $stage The migration stage
     *
     * @throws PrimeException
     */
    public function rollback(string $endpoint, string $stage = MigrationInterface::STAGE_DEFAULT): void
    {
        $versions = $this->getAvailableVersions($stage);
        rsort($versions);

        foreach ($versions as $version) {
            if ($version <= $endpoint) {
                break;
            }

            if ($this->isUp($version)) {
                $this->down($version);
            }
        }
    }

    /**
     * Run a migration in a particular direction
     *
     * @param MigrationInterface $migration
     * @param string $direction
     *
     * @return void
     */
    private function run(MigrationInterface $migration, string $direction): void
    {
        $this->applyConsoleContext($migration);

        $this->output->writeln(
            ' == <info>'.$migration->version().' '.$migration->name().'</info> '.
            '<comment>'.($direction === self::UP ? 'migrating' : 'reverting').'</comment>'
        );

        $start = microtime(true);
        $migration->initialize();
        $migration->{$direction}();
        $end = microtime(true);

        $this->output->writeln(
            ' == <info>'.$migration->version().' '.$migration->name().'</info> '.
            '<comment>'.($direction === self::UP ? 'migrated ' : 'reverted ').sprintf("%.4fs", $end - $start).'</comment>'
        );
    }

    /**
     * Add console context on the migration
     *
     * @param MigrationInterface $migration
     *
     * @return void
     */
    private function applyConsoleContext(MigrationInterface $migration)
    {
        if (!$migration instanceof Migration) {
            return;
        }

        $migration->setOutput($this->output);

        if (null !== $this->input) {
            $migration->setInput($this->input);
        }
        if (null !== $this->helper) {
            $migration->setHelperSet($this->helper);
        }
    }

    /**
     * @param OutputInterface $output
     *
     * @return void
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * @param InputInterface $input
     *
     * @return void
     */
    public function setInput(InputInterface $input): void
    {
        $this->input = $input;
    }

    /**
     * @param HelperSet $helper
     *
     * @return void
     */
    public function setHelper(HelperSet $helper): void
    {
        $this->helper = $helper;
    }
}
