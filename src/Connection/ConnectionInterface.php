<?php

namespace Bdf\Prime\Connection;

use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Exception\QueryBuildingException;
use Bdf\Prime\Exception\QueryExecutionException;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Query\CommandInterface;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;
use Bdf\Prime\Query\Contract\Compilable;
use Bdf\Prime\Query\Factory\QueryFactoryInterface;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Query\ReadCommandInterface;
use Bdf\Prime\Schema\Manager\DatabaseManagerInterface;
use Bdf\Prime\Schema\SchemaManagerInterface;
use Bdf\Prime\Types\TypeInterface;
use Doctrine\Common\EventManager;

/**
 * Base connection type
 *
 * Allows creating and executing queries, and handle a platform
 */
interface ConnectionInterface
{
    /**
     * Set the connection name
     * 
     * @param string $name
     * 
     * @return $this
     */
    public function setName(string $name);

    /**
     * Get the connection name
     * 
     * @return string
     */
    public function getName(): string;

    /**
     * Gets the SchemaManager.
     *
     * @return DatabaseManagerInterface
     * @throws PrimeException
     */
    public function schema(): DatabaseManagerInterface;

    /**
     * Transform database value to PHP value
     *
     * @param mixed $value
     * @param string|TypeInterface $type
     *
     * @param array $fieldOptions
     * @return mixed
     *
     * @throws PrimeException When cannot convert value
     */
    public function fromDatabase($value, $type, array $fieldOptions = []);

    /**
     * Transform PHP value to database value
     *
     * @param mixed $value
     * @param null|string|TypeInterface $type
     *
     * @return mixed
     *
     * @throws PrimeException When cannot convert value
     */
    public function toDatabase($value, $type = null);

    /**
     * Get a query builder
     *
     * @param PreprocessorInterface|null $preprocessor The compiler preprocessor to use
     *
     * @return ReadCommandInterface
     */
    public function builder(PreprocessorInterface $preprocessor = null): ReadCommandInterface;

    /**
     * Make a new query
     *
     * @param class-string<Q> $query The query name, or class name
     * @param PreprocessorInterface|null $preprocessor The compiler preprocessor to use
     *
     * @return Q
     *
     * @template Q as CommandInterface
     */
    public function make(string $query, PreprocessorInterface $preprocessor = null): CommandInterface;

    /**
     * Get the query factory for this connection
     *
     * @return QueryFactoryInterface
     */
    public function factory(): QueryFactoryInterface;

    /**
     * Get a select query builder from table
     *
     * @param string|QueryInterface $table The "from" clause. Can be a table name or an embedded query
     * @param string|null $alias The clause alias
     *
     * @return ReadCommandInterface
     */
    public function from($table, ?string $alias = null): ReadCommandInterface;

    /**
     * Executes a raw select query and returns array of object
     *
     * @param mixed $query The raw query to execute
     * @param array $bindings The query bindings
     *
     * @return ResultSetInterface<\stdClass> The database result, in object form
     * @throws PrimeException When select fail
     */
    public function select($query, array $bindings = []): ResultSetInterface;

    /**
     * Execute the query and get the result.
     * The result may differ by the query type
     *
     * @param Compilable $query Query to execute
     *
     * @return ResultSetInterface<array<string, mixed>>
     *
     * @see Compilable::type() The query type
     *
     * @throws QueryExecutionException When query execution fail
     * @throws QueryBuildingException When query compilation fail
     * @throws PrimeException When execution fail
     */
    public function execute(Compilable $query): ResultSetInterface;

    /**
     * Gets the name of the database this Connection is connected to.
     *
     * @return string|null
     */
    public function getDatabase(): ?string;

    /**
     * Get the platform instance
     *
     * @return PlatformInterface
     * @throws PrimeException
     */
    public function platform(): PlatformInterface;

    /**
     * Gets the EventManager used by the Connection.
     *
     * @return EventManager
     *
     * @todo Ne pas utiliser l'event manager de doctrine ?
     *       C'est actuellement le plus simple et léger, mais ajoute une dépendence forte à Doctrine
     *
     * @internal
     */
    public function getEventManager();

    /**
     * Closes the connection and trigger "onConnectionClosed" event
     *
     * @return void
     */
    public function close(): void;
}
