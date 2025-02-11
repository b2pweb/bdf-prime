<?php

namespace StaticAnalysis;

class MyRecord
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}
