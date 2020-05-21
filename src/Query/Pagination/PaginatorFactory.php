<?php

namespace Bdf\Prime\Query\Pagination;

use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Query\ReadCommandInterface;

/**
 * Factory for paginators classes
 */
class PaginatorFactory
{
    /**
     * Mapping for paginator classes
     *
     * @var string[]
     */
    static protected $paginatorAliases = [
        'walker'    => Walker::class,
        'paginator' => Paginator::class,
    ];

    
    /**
     * Create the paginator instance
     *
     * @param ReadCommandInterface $query The query to paginate
     * @param string $class The paginator class name
     * @param int|null $maxRows Number of entries by page
     * @param int|null $page The current page
     *
     * @return PaginatorInterface
     */
    public static function create(ReadCommandInterface $query, $class = 'paginator', $maxRows = null, $page = null)
    {
        if (isset(static::$paginatorAliases[$class])) {
            $class = static::$paginatorAliases[$class];
        }

        return new $class($query, $maxRows, $page);
    }
}
