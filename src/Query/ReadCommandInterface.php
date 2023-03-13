<?php

namespace Bdf\Prime\Query;

use Bdf\Prime\Collection\CollectionFactory;
use Bdf\Prime\Collection\CollectionInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Contract\Cachable;
use Bdf\Prime\Query\Contract\ReadOperation;

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
     *         ->inRows('name);
     *
     *     print_r($result);
     *     // display
     *        Array
     *        (
     *            [0] => 'foo'
     *            [1] => 'bar'
     *             ...
     * </code>
     *
     * @param string $column
     *
     * @return list<mixed>
     * @throws PrimeException When execute fail
     */
    #[ReadOperation]
    public function inRows(string $column): array;

    /**
     * Get a column value
     *
     * <code>
     *     $result = $query
     *         ->from('users')
     *         ->inRow('name);
     *
     *     echo $result;
     *     // display 'foo'
     * </code>
     *
     * @param string $column
     *
     * @return mixed
     * @throws PrimeException When execute fail
     */
    #[ReadOperation]
    public function inRow(string $column);
}
