<?php

namespace StaticAnalysis;

final class MyRecord
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {}
}
