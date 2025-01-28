<?php

namespace Bus\Fixtures;

use Bdf\Prime\Bus\Command\WriteQuery\PrimeDeleteCommand;
use Bdf\Prime\Query\Criteria\Criterion;
use Bdf\Prime\User;

#[PrimeDeleteCommand(User::class)]
class ManualDeleteCommand
{
    public function __construct(
        #[Criterion('customer.id')]
        public readonly int $customerId,
    ) {}
}
