<?php

namespace Bus\Fixtures;

use Bdf\Prime\Bus\Query\PrimeQuery;
use Bdf\Prime\Query\Criteria\StartsWithCriterion;
use Bdf\Prime\User;

#[PrimeQuery(entity: User::class)]
final class SearchUsersQuery
{
    public function __construct(
        #[StartsWithCriterion]
        public string $name,

        #[StartsWithCriterion(field: 'customer.id')]
        public ?string $customerId = null,
    ) {}
}
