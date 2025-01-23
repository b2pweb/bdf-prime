<?php

namespace Bus\Fixtures;

use Bdf\Prime\Bus\Query\FromRelation;
use Bdf\Prime\Bus\Query\PrimeQuery;
use Bdf\Prime\Customer;

#[PrimeQuery(connection: 'other')]
final class RelationOtherConnectionQuery
{
    public function __construct(
        #[FromRelation('users')]
        public readonly Customer $customer,
    ) {}
}
