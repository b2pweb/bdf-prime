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
     *
     * @var string
     */
    private $version;

    /**
     * The application container
     *
     * @var ContainerInterface
     */
    protected $di;

    /**
     * The console input.
     *
     * @var InputInterface
     */
    protected $input;

    /**
     * The console output.
     *
     * @var OutputInterface
     */
    protected $output;

    /**
     * The console helper.
     *
     * @var HelperSet
     */
    protected $helperSet;

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
    public function getHelperSet()
    {
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
    public function query($sql, array $params = [], $connectionName = null): Result
    {
        return $this->connection($connectionName)->executeQuery($sql, $params);
    }

    /**
     * Execute a update query
     *
     * @param string $sql
     * @param array  $params
     * @param string $connectionName
     *
     * @return int
     * @throws PrimeException
     */
    public function update($sql, array $params = [], $connectionName = null)
    {
        return (int) $this->connection($connectionName)->executeStatement($sql, $params);
    }

    /**
     * Get schema manager instance
     *
     * @param string $connectionName
     *
     * @return SchemaManager
     * @throws PrimeException
     */
    public function schema($connectionName = null)
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
    public function connection($connectionName = null)
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
    public function repository($entity)
    {
        return $this->prime()->repository($entity);
    }

    /**
     * Get prime service locator
     *
     * @return ServiceLocator
     */
    public function prime()
    {
        return $this->di->get('prime');
    }

    /**
     * Logger extension accessor
     *
     * @return LoggerInterface
     */
    public function log()
    {
        return $this->di->get('logger');
    }
}
