<?php

namespace Bdf\Prime\Migration\Version;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Migration\VersionRepositoryInterface;

/**
 * DbVersionRepository
 *
 * Contains the version of all upgraded migration
 */
class DbVersionRepository implements VersionRepositoryInterface
{
    /**
     * The db connection
     *
     * @var ConnectionInterface&\Doctrine\DBAL\Connection
     */
    private $connection;

    /**
     * The table name
     *
     * @var string
     */
    private $tableName;

    /**
     * Check whether the table exists
     *
     * @var boolean
     *
     * @internal
     */
    private $hasSchema;

    /**
     * Cache of version
     *
     * @var array
     *
     * @internal
     */
    private $cached;

    /**
     * Constructor
     *
     * @param ConnectionInterface&\Doctrine\DBAL\Connection $connection
     * @param string $tableName
     */
    public function __construct(ConnectionInterface $connection, string $tableName)
    {
        $this->connection = $connection;
        $this->tableName  = $tableName;
    }

    /**
     * Get the connection
     * 
     * @return ConnectionInterface&\Doctrine\DBAL\Connection
     */
    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * Get the table name
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->tableName;
    }

    /**
     * {@inheritDoc}
     */
    public function newIdentifier(): string
    {
        return date('YmdHis');
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $version): bool
    {
        return in_array($version, $this->all());
    }

    /**
     * {@inheritDoc}
     */
    public function current(): string
    {
        $versions = $this->all();

        return (string)end($versions);
    }

    /**
     * {@inheritDoc}
     */
    public function all(): array
    {
        if ($this->cached !== null) {
            return $this->cached;
        }

        $this->prepare();

        /** @psalm-suppress UndefinedInterfaceMethod */
        return $this->cached = $this->connection->from($this->tableName)->order('version')->inRows('version');
    }

    /**
     * {@inheritDoc}
     */
    public function add(string $version)
    {
        $this->prepare();

        $this->connection->insert($this->tableName, [
            'version' => $version,
        ]);

        $this->cached = null;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $version)
    {
        $this->prepare();

        $this->connection->delete($this->tableName, [
            'version' => $version,
        ]);

        $this->cached = null;

        return $this;
    }

    /**
     * Prepare the repository
     * @throws PrimeException
     */
    public function prepare(): void
    {
        if (!$this->hasSchema()) {
            $this->createSchema();
        }
    }

    /**
     * Is the schema ready? 
     *
     * @return boolean
     * @throws PrimeException
     */
    public function hasSchema(): bool
    {
        if (null === $this->hasSchema) {
            $this->hasSchema = $this->connection->schema()->hasTable($this->tableName);
        }

        return $this->hasSchema;
    }

    /**
     * Create Schema
     *
     * @return $this
     * @throws PrimeException
     */
    public function createSchema()
    {
        $this->connection->schema()->table($this->tableName, function($table) {
            $table->string('version');
        });

        $this->hasSchema = true;
        
        return $this;
    }
}
