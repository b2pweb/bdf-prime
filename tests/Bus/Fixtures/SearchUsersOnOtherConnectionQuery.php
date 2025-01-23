<?php

namespace Bus\Fixtures;

use Bdf\Prime\Bus\Query\PrimeQuery;
use Bdf\Prime\Query\Criteria\StartsWithCriterion;
use Bdf\Prime\User;

#[PrimeQuery(entity: User::class, connection: 'other')]
final class SearchUsersOnOtherConnectionQuery
{
    public function __construct(
        #[StartsWithCriterion]
        public string $name,
    ) {}
}
