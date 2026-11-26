<?php

namespace Bdf\Prime\Migration;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Repository\RepositoryInterface;
use Bdf\Prime\Schema\SchemaManager;
use Bdf\Prime\ServiceLocator;
use Doctrine\DBAL\Result;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Migration
 */
class Migration implements MigrationInterface
{
    /**
     * The migration version
     */
    private string $version;

    /**
     * The application container
     */
    protected ContainerInterface $di;

    /**
     * The console input.
     *
     * @var InputInterface|null
     */
    protected ?InputInterface $input = null;

    /**
     * The console output.
     *
     * @var OutputInterface|null
     */
    protected ?OutputInterface $output = null;

    /**
     * The console helper.
     *
     * @var HelperSet|null
     */
    protected ?HelperSet $helperSet = null;

    /**
     * Migration constructor
     *
     * @param string $version
     * @param ContainerInterface $di
     */
    public function __construct(string $version, ContainerInterface $di)
    {
        $this->version = $version;
        $this->di = $di;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(): void
    {
        // To overwrite
    }

    /**
     * {@inheritdoc}
     */
    public function up(): void
    {
        // To overwrite
    }

    /**
     * {@inheritdoc}
     */
    public function down(): void
    {
        // To overwrite
    }

    /**
     * {@inheritdoc}
     */
    public function stage(): string
    {
        return self::STAGE_DEFAULT;
    }

    /**
     * Get migration version (migration ID)
     *
     * @return string
     */
    final public function version(): string
    {
        return $this->version;
    }

    /**
     * Get migration name
     *
     * @return string
     */
    public function name(): string
    {
        return get_class($this);
    }

    /**
     * Get the console input
     *
     * @return InputInterface
     */
    public function getInput(): InputInterface
    {
        if ($this->input === null) {
            throw new \LogicException('Console input is not set.');
        }

        return $this->input;
    }

    /**
     * Set the console input
     *
     * @param InputInterface $input
     *
     * @return void
     */
    public function setInput(InputInterface $input): void
    {
        $this->input = $input;
    }

    /**
     * Get the console output
     *
     * @return OutputInterface
     */
    public function getOutput(): OutputInterface
    {
        if ($this->output === null) {
            throw new \LogicException('Console output is not set.');
        }

        return $this->output;
    }

    /**
     * Set the console output
     *
     * @param OutputInterface $output
     *
     * @return void
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * Sets the helper set.
     *
     * @param HelperSet $helperSet A HelperSet instance
     *
     * @return void
     */
    public function setHelperSet(HelperSet $helperSet): void
    {
        $this->helperSet = $helperSet;
    }

    /**
     * Gets the helper set.
     *
     * @return HelperSet A HelperSet instance
     */
    public function getHelperSet(): HelperSet
    {
        if ($this->helperSet === null) {
            throw new \LogicException('Console helper set is not set.');
        }

        return $this->helperSet;
    }

    /**
     * Execute a select query
     *
     * @param string $sql
     * @param array  $params
     * @param string $connectionName
     *
     * @return Result
     * @throws PrimeException
     */
    public function query(string $sql, array $params = [], ?string $connectionName = null): Result
    {
        return $this->connection($connectionName)->executeQuery($sql, $params);
    }

    /**
     * Execute a update query
     *
     * @param string $sql
     * @param array  $params
     * @param string|null $connectionName
     *
     * @return int
     * @throws PrimeException
     */
    public function update(string $sql, array $params = [], ?string $connectionName = null): int
    {
        $conn = $this->connection($connectionName);

        if ($conn->getParameters()['ignore'] ?? false) {
            $logger = $this->log();

            if ($logger) {
                $logger->info('Migration skipped because the connection {connection} is set to ignore changes', [
                    'connection' => $conn->getName(),
                    'sql' => $sql,
                ]);
            }
            return 0;
        }

        return (int) $conn->executeStatement($sql, $params);
    }

    /**
     * Check if the connection should be ignored (i.e. should not apply schema changes)
     *
     * @param string|null $connectionName
     * @return bool
     */
    public function isIgnoredConnection(?string $connectionName = null): bool
    {
        $conn = $this->connection($connectionName);

        return $conn->getParameters()['ignore'] ?? false;
    }

    /**
     * Get schema manager instance
     *
     * @param string|null $connectionName
     *
     * @return SchemaManager
     * @throws PrimeException
     */
    public function schema(?string $connectionName = null): SchemaManager
    {
        return new SchemaManager($this->connection($connectionName));
    }

    /**
     * Get db connection
     *
     * @param string|null $connectionName
     *
     * @return ConnectionInterface&\Doctrine\DBAL\Connection
     */
    public function connection(?string $connectionName = null): ConnectionInterface
    {
        /** @var ConnectionInterface&\Doctrine\DBAL\Connection */
        return $this->prime()->connection($connectionName);
    }

    /**
     * Get entity repository
     *
     * @param class-string<E>|E $entity
     *
     * @return RepositoryInterface<E>
     *
     * @template E as object
     */
    public function repository(string|object $entity): RepositoryInterface
    {
        return $this->prime()->repository($entity);
    }

    /**
     * Get prime service locator
     *
     * @return ServiceLocator
     */
    public function prime(): ServiceLocator
    {
        return $this->di->get('prime');
    }

    /**
     * Logger extension accessor
     *
     * @return LoggerInterface|null
     */
    public function log(): ?LoggerInterface
    {
        return $this->di->get('logger');
    }
}
