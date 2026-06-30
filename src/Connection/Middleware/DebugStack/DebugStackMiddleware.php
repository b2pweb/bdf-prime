<?php

namespace Bdf\Prime\Connection\Middleware\DebugStack;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;
use Override;

final readonly class DebugStackMiddleware implements Middleware
{
    public function __construct(
        private DebugStack $debugStack,
    ) {}

    #[Override]
    public function wrap(Driver $driver): Driver
    {
        return new DebugStackDriver($driver, $this->debugStack);
    }
}
