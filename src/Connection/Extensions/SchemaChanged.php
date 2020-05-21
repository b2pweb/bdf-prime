<?php

namespace Bdf\Prime\Connection\Extensions;

/**
 * Trait SchemaChanged
 */
trait SchemaChanged
{
    /**
     * @var string[]
     */
    private $resetStatement = [
        'database schema has changed', // SQLite php 7.1
        'bad parameter or other API misuse', // SQLite php 7.3
        'library routine called out of sequence', // SQLite php 7.4
    ];

    /**
     * Determine if the given exception was caused by a schema change
     *
     * @param \Exception $exception
     *
     * @return bool
     */
    protected function causedBySchemaChange(\Exception $exception)
    {
        foreach ($this->resetStatement as $error) {
            if (strpos($exception->getMessage(), $error) !== false) {
                return true;
            }
        }

        return false;
    }
}
