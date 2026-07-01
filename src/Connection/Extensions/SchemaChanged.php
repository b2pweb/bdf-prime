<?php

namespace Bdf\Prime\Connection\Extensions;

use function str_contains;

/**
 * Trait SchemaChanged
 */
trait SchemaChanged
{
    /**
     * @var string[]
     */
    private array $resetStatement = [
        'database schema has changed', // SQLite php 7.1
        'bad parameter or other API misuse', // SQLite php 7.3
        'library routine called out of sequence', // SQLite php 7.4
    ];

    /**
     * Determine if the given exception was caused by a schema change
     *
     * @param \Throwable $exception
     *
     * @return bool
     */
    protected function causedBySchemaChange(\Throwable $exception): bool
    {
        foreach ($this->resetStatement as $error) {
            if (str_contains($exception->getMessage(), $error)) {
                return true;
            }
        }

        return false;
    }
}
