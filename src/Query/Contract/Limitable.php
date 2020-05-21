<?php

namespace Bdf\Prime\Query\Contract;

/**
 * Interface for query with limits, offset and pagination
 */
interface Limitable
{
    /**
     * Limit executed query to specified amount of records
     * Implemented at adapter-level for databases that support it
     *
     * @param int $limit Number of records to return
     * @param int $offset Record to start at for limited result set
     *
     * @return $this This Query instance.
     */
    public function limit($limit, $offset = null);

    /**
     * Sets the limit and count by page number.
     *
     * @todo l'api devrait etre "limitPage($rowCount, $page = 1)"
     *
     * @param int $page Limit results to this page number.
     * @param int $rowCount Use this many rows per page.
     *
     * @return $this This Query instance.
     */
    public function limitPage($page, $rowCount = 1);

    /**
     * Get the page of pagination
     *
     * @return int
     */
    public function getPage();

    /**
     * Get limit value
     *
     * @return int
     */
    public function getLimit();

    /**
     * Offset executed query to skip specified amount of records
     * Implemented at adapter-level for databases that support it
     *
     * @param int $offset Record to start at for limited result set
     *
     * @return $this This Query instance.
     */
    public function offset($offset);

    /**
     * Get offset value
     *
     * @return int
     */
    public function getOffset();

    /**
     * Check if query has limit attribute
     *
     * @return bool
     */
    public function isLimitQuery();

    /**
     * Check if query has pagination
     * Is true whether the query has first result AND max result set
     *
     * @return bool
     */
    public function hasPagination();
}
