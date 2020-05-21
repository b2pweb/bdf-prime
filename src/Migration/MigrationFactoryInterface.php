<?php

namespace Bdf\Prime\Migration;

/**
 * Migration file provider
 */
interface MigrationFactoryInterface
{
    /**
     * Create a migration object
     *
     * @param string $className
     * @param string $version
     *
     * @return Migration
     *
     * @throws \InvalidArgumentException
     */
    public function create(string $className, string $version): MigrationInterface;
}
