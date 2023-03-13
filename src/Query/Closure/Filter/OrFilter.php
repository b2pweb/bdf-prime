<?php

namespace Bdf\Prime\Query\Closure\Filter;

final class OrFilter
{
    /**
     * @var list<AndFilter>
     */
    public array $filters;

    /**
     * @param list<AndFilter> $filters
     */
    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }
}
