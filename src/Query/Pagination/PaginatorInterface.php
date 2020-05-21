<?php

namespace Bdf\Prime\Query\Pagination;

use Bdf\Prime\Collection\CollectionInterface;

/**
 * PaginatorInterface
 * 
 * @author  Seb
 * @package Bdf\Prime\Query\Pagination
 */
interface PaginatorInterface extends CollectionInterface
{
    /**
     * Get the current collection
     * 
     * @return array|CollectionInterface
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
     * @return string|array
     */
    public function order($attribute = null);

    /**
     * Get query limit
     * 
     * @return int
     */
    public function limit();

    /**
     * Get query offset
     * 
     * @return int
     */
    public function offset();

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