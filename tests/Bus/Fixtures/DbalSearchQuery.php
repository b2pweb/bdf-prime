<?php

namespace Bus\Fixtures;

use Bdf\Prime\Bus\From;
use Bdf\Prime\Bus\PrimeQuery;
use Bdf\Prime\Bus\QueryExecutionMethod;
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
