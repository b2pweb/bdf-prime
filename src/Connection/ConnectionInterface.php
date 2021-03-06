<?php

namespace Bdf\Prime\Connection;

use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\Exception\DBALException;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Query\CommandInterface;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;
use Bdf\Prime\Query\Contract\Compilable;
use Bdf\Prime\Query\Factory\QueryFactoryInterface;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Schema\SchemaManagerInterface;
use Bdf\Prime\Types\TypeInterface;
use Doctrine\Common\EventManager;

/**
 * ConnectionInterface
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
    public function setName($name);

    /**
     * Get the connection name
     * 
     * @return string
     */
    public function getName();

    /**
     * Gets the SchemaManager.
     *
     * @return SchemaManagerInterface
     * @throws PrimeException
     */
    public function schema();

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
     * @return CommandInterface
     */
    public function builder(PreprocessorInterface $preprocessor = null);

    /**
     * Make a new query
     *
     * @param string $query The query name, or class name
     * @param PreprocessorInterface|null $preprocessor The compiler preprocessor to use
     *
     * @return CommandInterface
     */
    public function make($query, PreprocessorInterface $preprocessor = null);

    /**
     * Get the query factory for this connection
     *
     * @return QueryFactoryInterface
     */
    public function factory();

    /**
     * Get a select query builder from table
     *
     * @param string|QueryInterface $table
     * @param string $alias
     *
     * @return QueryInterface
     */
    public function from($table, string $alias = null);

    /**
     * Executes a raw select query and returns array of object
     *
     * @param string $query
     * @param array  $bindings
     * @param array  $types
     *
     * @return array The database result
     * @throws PrimeException When select fail
     */
    public function select($query, array $bindings = [], array $types = []);

    /**
     * Execute the query and get the result
     * The result may differ by the query type
     *
     * @param Compilable $query
     *
     * @return ResultSetInterface
     *
     * @see Compilable::type() The query type
     * @throws PrimeException When execution fail
     */
    public function execute(Compilable $query);

    /**
     * Gets the name of the database this Connection is connected to.
     *
     * @return string
     */
    public function getDatabase();

    /**
     * Get the platform instance
     *
     * @return PlatformInterface
     * @throws PrimeException
     */
    public function platform();

    /**
     * Gets the EventManager used by the Connection.
     *
     * @return EventManager
     *
     * @todo Ne pas utiliser l'event manager de doctrine ?
     *       C'est actuellement le plus simple et léger, mais ajoute une dépendence forte à Doctrine
     */
    public function getEventManager();
}
