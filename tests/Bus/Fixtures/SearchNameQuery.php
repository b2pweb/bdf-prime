<?php

namespace Bus\Fixtures;

use Bdf\Prime\Bus\Query\Configurator\Projection;
use Bdf\Prime\Bus\Query\Execution\QueryExecutionMethod;
use Bdf\Prime\Bus\Query\PrimeQuery;
use Bdf\Prime\Query\Criteria\StartsWithCriterion;

#[PrimeQuery(method: QueryExecutionMethod::First), Projection('name')]
final class SearchNameQuery
{
    public function __construct(
        #[StartsWithCriterion]
        public string $name,
    ) {}
}
