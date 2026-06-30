<?php

namespace Bdf\Prime\Connection\Middleware\DebugStack;

final class DebugStack
{
    /**
     * @var list<DebugQuery>
     */
    public private(set) array $queries = [];

    public function push(DebugQuery $query): void
    {
        $this->queries[] = $query;
    }

    public function clear(): void
    {
        $this->queries = [];
    }
}
