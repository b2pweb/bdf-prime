<?php

namespace Bdf\Prime\Query;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Contract\ReadOperation;

/**
 * Extension for compatibility purposes
 */
class QueryCompatExtension
{
    /**
     * @param QueryInterface $query
     * @param $flag
     *
     * @return QueryInterface
     */
    public function ignore(QueryInterface $query, $flag = true)
    {
        return $query;
    }

    /**
     * @param QueryInterface $query
     * @param null|array $attributes
     *
     * @return int
     * @throws PrimeException
     */
    #[ReadOperation]
    public function count(QueryInterface $query, $attributes = null)
    {
        return count($query->all($attributes));
    }
}
