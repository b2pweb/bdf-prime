<?php

namespace Bdf\Prime\Query;

use Bdf\Prime\Collection\CollectionFactory;
use Bdf\Prime\Collection\CollectionInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Contract\Cachable;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Expression\ExpressionInterface;

/**
 * Base type for "read" operation commands
 *
 * @method $this by(string $attribute, bool $combine = false) Indexing entities by an attribute value. Use combine for multiple entities with same attribute value
 * @method $this with(string|string[] $relations) Relations to load
 * @method $this without(string|string[] $relations) Relations to discard
 * @method $this filter(\Closure $predicate) Filter entities
 * @method R|null get($pk) Get one entity by its identifier
 * @method R getOrFail($pk) Get one entity or throws when entity is not found
 * @method R getOrNew($pk) Get one entity or return a new one if not found in repository
 * @method R|null findById(mixed|array $pk) Get one entity by its primary key or null if not found in repository
 * @method R findByIdOrFail(mixed|array $pk) Get one entity by its primary key or throws if not found in repository
 * @method R findByIdOrNew(mixed|array $pk) Get one entity by its primary key or return a new one if not found in repository, using the where criteria as default values
 * @method R firstOrFail() Get the first result of the query, or throws an exception if no result
 * @method R firstOrNew(bool $useCriteriaAsDefault = true) Get the first result of the query, or create a new instance if no result. If $useCriteriaAsDefault is true, the where criteria will be used as default values for the new instance.
 * @method array<string, mixed>|null toCriteria() Transform the query where clause to simple key/value criteria. Return null if the query is not a simple criteria.
 *
 * @template C as \Bdf\Prime\Connection\ConnectionInterface
 * @template R as object|array
 *
 * @extends CommandInterface<C>
 */
interface ReadCommandInterface extends CommandInterface, Cachable
{
    /**
     * Register an object that can extends the Query methods.
     * The extension will be called when the method cannot be found on the query object.
     *
     * <pre><code>
     * class MyExtension {
     *     public function myCustomMethod(QueryInterface $query, $param)
     *     {
     *         $query->where(...);
     *         return $query;
     *     }
     * }
     *
     * $query->setExtension(new MyExtension());
     *
     * $query->myCustomMethod(...); // Will call MyExtension->myCustomMethod()
     * </pre></code>
     *
     * @param object $extension
     *
     * @return void
     */
    public function setExtension($extension): void;

    /**
     * Get the collection factory
     *
     * @return CollectionFactory
     */
    public function collectionFactory(): CollectionFactory;

    /**
     * Set the collection factory
     *
     * @param CollectionFactory $collectionFactory
     *
     * @return $this
     */
    public function setCollectionFactory(CollectionFactory $collectionFactory);

    /**
     * Set a post processor to transform query result
     *
     * @param (EACH is true ? callable(array<string, mixed>):mixed : callable(\Bdf\Prime\Connection\Result\ResultSetInterface<array<string, mixed>>):array) $processor
     * @param EACH $forEach
     *
     * @return $this
     *
     * @template EACH as bool
     */
    public function post(callable $processor, bool $forEach = true);

    /**
     * Set the collection class
     *
     * @param string $wrapperClass
     *
     * @return $this
     */
    public function wrapAs(string $wrapperClass);

    /**
     * Get all matched data.
     * The all method run the post processors
     *
     * <code>
     *     $query
     *         ->from('users')
     *         ->all('name);
     * </code>
     *
     * @param string|array $columns
     *
     * @return R[]|CollectionInterface<R>
     *
     * @throws PrimeException When execute fail
     */
    #[ReadOperation]
    public function all($columns = null);

    /**
     * Get first matched data
     *
     * <code>
     *     $query
     *         ->from('users')
     *         ->first('name);
     * </code>
     *
     * @param string|array $columns
     *
     * @return R|null
     * @throws PrimeException When execute fail
     */
    #[ReadOperation]
    public function first($columns = null);

    /**
     * Get a collection of column value
     *
     * <code>
     *     $result = $query
     *         ->from('users')
     *         ->inRows('name');
     *
     *     print_r($result);
     *     // display
     *        Array
     *        (
     *            [0] => 'foo'
     *            [1] => 'bar'
     *             ...
     *
     *      // Expressions can also be used
     *      $query->from('users')->inRows(new Raw('options->>"$.darkMode"'));
     * </code>
     *
     * @param string|ExpressionInterface $column The column to return, or an expression
     *
     * @return list<mixed>
     * @throws PrimeException When execute fail
     *
     * @psalm-suppress MismatchingDocblockParamType
     * @todo Change parameter type hint on prime 3.0
     */
    #[ReadOperation]
    public function inRows(string/*|ExpressionInterface*/ $column): array;

    /**
     * Get a column value
     *
     * <code>
     *     $result = $query
     *         ->from('users')
     *         ->inRow('name');
     *
     *     echo $result;
     *     // display 'foo'
     *
     *     // Expressions can also be used
     *     $query->from('users')->inRow(new Raw('options->>"$.darkMode"'));
     * </code>
     *
     * @param string|ExpressionInterface $column The column to return, or an expression
     *
     * @return mixed
     * @throws PrimeException When execute fail
     *
     * @psalm-suppress MismatchingDocblockParamType
     * @todo Change parameter type hint on prime 3.0
     */
    #[ReadOperation]
    public function inRow(string/*|ExpressionInterface*/ $column);
}
