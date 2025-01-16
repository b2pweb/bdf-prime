<?php

namespace Bus\Fixtures;

use Bdf\Prime\Bus\PrimeQuery;
use Bdf\Prime\Bus\Projection;
use Bdf\Prime\Bus\QueryExecutionMethod;
use Bdf\Prime\Query\Criteria\StartsWithCriterion;

#[PrimeQuery(method: QueryExecutionMethod::First), Projection('name')]
final class SearchNameQuery
{
    public function __construct(
        #[StartsWithCriterion]
        public string $name,
    ) {}
}
