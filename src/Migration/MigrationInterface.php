<?php

namespace Bdf\Prime\Migration;

/**
 * Migration
 */
interface MigrationInterface
{
    const STAGE_DEFAULT = 'default';
    const STAGE_PREPARE = 'prepare';

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
    public function stage();

    /**
     * Initialize the migration
     */
    public function initialize();

    /**
     * Do the migration
     */
    public function up();

    /**
     * Undo the migration
     */
    public function down();
}
