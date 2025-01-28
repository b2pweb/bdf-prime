<?php

namespace Bus\Fixtures;

use Bdf\Prime\Bus\Command\SetValue;
use Bdf\Prime\Bus\Command\WriteQuery\PrimeUpdateCommand;
use Bdf\Prime\Query\Criteria\Criterion;
use Bdf\Prime\User;

#[PrimeUpdateCommand(User::class)]
class ManualUpdateCommand
{
    public function __construct(
        #[Criterion('customer.id')]
        public readonly int $customerId,

        #[SetValue('roles')]
        public readonly array $newRoles,
    ) {}
}
