<?php

namespace Bdf\Prime\Query\Compiled;

use Bdf\Prime\Collection\CollectionFactory;
use Bdf\Prime\Query\Contract\Cachable;
use Bdf\Prime\Query\Contract\Compilable;
use Bdf\Prime\Query\Contract\Executable;
use Bdf\Prime\Query\QueryRepositoryExtension;

/**
 * Query for constant and compiled SQL query
 *
 * This class is immutable, so it can be reused and shared, and all modifications return a new instance
 *
 * @template R as object|array
 * @extends  Executable<R>
 */
interface CompiledQueryInterface extends Executable, Compilable, Cachable
{
    /**
     * Change the bindings of the query
     * A new instance is returned
     *
     * @param array $bindings
     *
     * @return static
     */
    public function withBindings(array $bindings): self;

    /**
     * Apply extra metadata on the query
     *
     * Those metadata depends on the implementation, and it's extracted using {@see JitCompilable::getMetadata()}
     * It allows to configure the result wrapper or cache for example
     *
     * @param array $metadata
     * @return static The new instance with configured metadata
     */
    public function withMetadata(array $metadata): self;

    /**
     * Apply extension configure on the query
     *
     * Those metadata are extracted using {@see JitCompilable::getExtension()}
     * It allows to configure the loaded relations for example
     *
     * @param array $metadata
     * @return static The new instance with configured extension
     */
    public function withExtensionMetadata(array $metadata): self;

    /**
     * Set the collection class
     *
     * @param string $wrapperClass
     *
     * @return static The new instance with configured wrapper
     * @see ReadCommandInterface::wrapAs()
     */
    public function wrapAs(string $wrapperClass);

    /**
     * Define the table for cache namespace
     *
     * @return $this
     * @internal
     */
    public function setTable(?string $table): self;

    /**
     * @return $this
     * @internal
     */
    public function setCollectionFactory(CollectionFactory $collectionFactory): self;

    /**
     * @return $this
     * @internal
     */
    public function setExtension(?QueryRepositoryExtension $extension): self;
}
