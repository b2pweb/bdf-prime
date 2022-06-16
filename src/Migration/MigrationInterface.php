<?php

namespace Bdf\Prime\Migration;

/**
 * Migration
 */
interface MigrationInterface
{
    public const STAGE_DEFAULT = 'default';
    public const STAGE_PREPARE = 'prepare';

    /**
     * Get migration name
     *
     * @return string
     */
    public function name(): string;

    /**
     * Get migration version (migration ID)
     *
     * @return string
     */
    public function version(): string;

    /**
     * Get the migration stage
     * A stage is used to separate migration process like before / after schema upgrade
     *
     * @return string
     */
    public function stage(): string;

    /**
     * Initialize the migration
     */
    public function initialize(): void;

    /**
     * Do the migration
     */
    public function up(): void;

    /**
     * Undo the migration
     */
    public function down(): void;
}
