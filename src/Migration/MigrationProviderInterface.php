<?php

namespace Bdf\Prime\Migration;

/**
 * Migration file provider
 */
interface MigrationProviderInterface
{
    /**
     * Initialize the directory of migrations
     */
    public function initRepository(): void;

    /**
     * Create a new migration file
     *
     * Valid options :
     * - stage (string) : The stage identifier
     *
     * @param string $version
     * @param string $name
     * @param string $stage The generation options
     *
     * @return string  Returns the file name
     */
    public function create(string $version, string $name, string $stage = MigrationInterface::STAGE_DEFAULT): string;

    /**
     * Get the directory path of migration files
     *
     * @return string
     */
    public function path(): string;

    /**
     * Get all migration name by version
     *
     * @return MigrationInterface[]
     */
    public function all(): array;

    /**
     * Get a migration by its version
     *
     * @param string $version
     *
     * @return MigrationInterface
     */
    public function migration($version): MigrationInterface;

    /**
     * Check whether the version has a migration
     *
     * @param string $version
     *
     * @return boolean
     */
    public function has($version): bool;

    /**
     * Import migration name by version from the directory path
     */
    public function import(): void;
}