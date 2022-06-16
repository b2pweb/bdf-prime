<?php

namespace Bdf\Prime\Query\Pagination;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Query\ReadCommandInterface;

/**
 * Factory for paginators classes
 */
class PaginatorFactory
{
    /**
     * @var PaginatorFactory
     */
    private static $instance;

    /**
     * Mapping for paginator classes
     *
     * @var string[]
     */
    private $paginatorAliases = [
        'walker'    => Walker::class,
        'paginator' => Paginator::class,
    ];

    /**
     * The paginator factory
     * Takes the class name as key and the factory function as value
     *
     * @var callable[]
     */
    private $paginatorFactories = [];

    /**
     * Register an alias for a paginator class
     *
     * @param string $className The paginator class name
     * @param string $alias The alias
     */
    public function addAlias(string $className, string $alias): void
    {
        $this->paginatorAliases[$alias] = $className;
    }

    /**
     * Register a factory for a paginator class
     *
     * @param string $className The paginator class name
     * @param callable $factory The factory function
     */
    public function addFactory(string $className, callable $factory): void
    {
        $this->paginatorFactories[$className] = $factory;
    }

    /**
     * Create the paginator instance
     *
     * @param ReadCommandInterface<ConnectionInterface, R> $query The query to paginate
     * @param string $class The paginator class name
     * @param int|null $maxRows Number of entries by page
     * @param int|null $page The current page
     *
     * @return PaginatorInterface<R>
     *
     * @template R as array|object
     */
    public function create(ReadCommandInterface $query, string $class = 'paginator', ?int $maxRows = null, ?int $page = null): PaginatorInterface
    {
        if (isset($this->paginatorAliases[$class])) {
            $class = $this->paginatorAliases[$class];
        }

        if (!isset($this->paginatorFactories[$class])) {
            return new $class($query, $maxRows, $page);
        }

        return ($this->paginatorFactories[$class])($query, $maxRows, $page);
    }

    /**
     * Get the paginator instance
     *
     * @return self
     */
    public static function instance(): self
    {
        if (self::$instance) {
            return self::$instance;
        }

        return self::$instance = new self();
    }
}
