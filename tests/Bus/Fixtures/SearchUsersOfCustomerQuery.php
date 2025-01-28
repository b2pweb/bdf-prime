<?php

namespace Bus\Fixtures;

use Bdf\Prime\Bus\Query\FromRelation;
use Bdf\Prime\Bus\Query\PrimeQuery;
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
