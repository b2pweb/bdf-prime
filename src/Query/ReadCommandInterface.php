<?php

namespace Bdf\Prime\Query;

use Bdf\Prime\Collection\CollectionFactory;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Contract\Cachable;
use Bdf\Prime\Query\Contract\ReadOperation;

/**
 * Base type for "read" operation commands
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
     */
    public function setExtension($extension);

    /**
     * Get the collection factory
     *
     * @return CollectionFactory
     */
    public function collectionFactory();

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
     * @param callable $processor
     * @param bool $forEach
     *
     * @return $this
     */
    public function post(callable $processor, $forEach = true);

    /**
     * Set the collection class
     *
     * @param string $wrapperClass
     *
     * @return $this
     */
    public function wrapAs($wrapperClass);

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
     * @return array
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
     * @return array|object|null
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
     * @return array
     * @throws PrimeException When execute fail
     */
    #[ReadOperation]
    public function inRows($column);

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
     * @return string
     * @throws PrimeException When execute fail
     */
    #[ReadOperation]
    public function inRow($column);
}
