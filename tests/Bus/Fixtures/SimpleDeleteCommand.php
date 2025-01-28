<?php

namespace Bus\Fixtures;

use Bdf\Prime\Bus\Command\WriteMethod\Delete;
use Bdf\Prime\User;

class SimpleDeleteCommand
{
    public function __construct(
        #[Delete]
        public readonly User $user,
    ) {}
}
