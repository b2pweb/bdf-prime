<?php

namespace Bdf\Prime\Query\Pagination;

use Bdf\Prime\Collection\CollectionInterface;
use Bdf\Prime\Query\Contract\Orderable;

/**
 * PaginatorInterface
 *
 * @template R as array|object
 * @extends CollectionInterface<R>
 */
interface PaginatorInterface extends CollectionInterface
{
    /**
     * Get the current collection
     *
     * @return R[]|CollectionInterface<R>
     */
    public function collection();

    /**
     * Get size of the complete collection
     *
     * This collection is countable. This means you can know
     * the number of entities matched by the query
     *
     * @return int
     */
    public function size();

    /**
     * Get query order
     * Get the attribute order type
     *
     * Return null if attribute is not ordered
     * If attribute is null array of order will be return
     *
     * @param string $attribute
     *
     * @return Orderable::ORDER_*|array<string,Orderable::ORDER_*>|null
     */
    public function order($attribute = null);

    /**
     * Get query limit
     *
     * @return int|null
     */
    public function limit(): ?int;

    /**
     * Get query offset
     *
     * @return int|null
     */
    public function offset(): ?int;

    /**
     * Get page from pagination
     *
     * @return int
     */
    public function page();

    /**
     * Get max rows in page
     *
     * @return int
     */
    public function pageMaxRows();
}
