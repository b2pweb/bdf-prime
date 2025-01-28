<?php

namespace Bus\Fixtures;

use Bdf\Prime\Bus\Command\WriteMethod\Insert;
use Bdf\Prime\User;

class SimpleInsertCommand
{
    public function __construct(
        #[Insert]
        public readonly User $user,
    ) {}
}
