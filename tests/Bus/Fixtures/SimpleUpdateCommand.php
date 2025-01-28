<?php

namespace Bus\Fixtures;

use Bdf\Prime\Bus\Command\WriteMethod\Update;
use Bdf\Prime\User;

class SimpleUpdateCommand
{
    public function __construct(
        #[Update(['roles'])]
        public readonly User $user,
    ) {}
}
