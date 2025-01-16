<?php

namespace Bus\Fixtures;

use Bdf\Prime\Bus\FromRelation;
use Bdf\Prime\Bus\PrimeQuery;
use Bdf\Prime\Customer;
use Bdf\Prime\Query\Criteria\StartsWithCriterion;

#[PrimeQuery]
final class SearchUsersOfCustomerQuery
{
    public function __construct(
        #[FromRelation('users')]
        public readonly Customer $customer,

        #[StartsWithCriterion]
        public readonly ?string $name = null,
    ) {}
}
