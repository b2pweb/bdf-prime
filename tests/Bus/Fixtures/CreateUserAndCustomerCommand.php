<?php

namespace Bus\Fixtures;

use Bdf\Prime\Bus\Command\WriteMethod\Insert;
use Bdf\Prime\Customer;
use Bdf\Prime\User;

class CreateUserAndCustomerCommand
{
    public function __construct(
        #[Insert]
        public readonly Customer $customer,

        #[Insert]
        public readonly User $user,
    ) {}
}
