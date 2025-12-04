<?php

namespace Bdf\Prime\Query\Criteria;

use IteratorAggregate;

/**
 * @extends IteratorAggregate<string, mixed>
 */
interface CriteriaInterface extends IteratorAggregate
{
    /**
     * The separator operator to use between each criterion
     *
     * Should be "AND" or "OR".
     * If null, the separator specified by the query will be used (e.g. "AND" for where(), "OR" for orWhere()).
     *
     * Note: this value will be directly used in the query, so it should be a valid SQL operator,
     *       and must never be passed directly from user input.
     *
     * @return string|null
     */
    public function separator(): ?string;
}
