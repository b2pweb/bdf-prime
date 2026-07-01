<?php

namespace Bdf\Prime\Connection\Middleware\DebugStack;

final readonly class DebugQuery
{
    public function __construct(
        public string $sql,
        public array $parameters,
    ) {
    }
}
