<?php

namespace Bus\Fixtures;

use Bdf\Prime\Bus\Query\Configurator\From;
use Bdf\Prime\Bus\Query\Execution\QueryExecutionMethod;
use Bdf\Prime\Bus\Query\PrimeQuery;
use Bdf\Prime\Query\Criteria\Criterion;

#[
    PrimeQuery(connection: 'test', method: QueryExecutionMethod::First),
    From('user_')
]
final class DbalSearchQuery
{
    public function __construct(
        #[Criterion('id_')]
        public readonly int $id,
    ) {}
}
