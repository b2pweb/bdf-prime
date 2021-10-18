<?php

use Bdf\Prime\Migration\Migration;

class Migration300 extends Migration
{
    /**
     * Initialize the migration
     */
    public function initialize(): void
    {
        $this->output->writeln('Init ' . $this->version());
    }

    /**
     * Do the migration
     */
    public function up(): void
    {
        $this->output->writeln('Up ' . $this->version());
    }

    /**
     * Undo the migration
     */
    public function down(): void
    {
        $this->output->writeln('Down ' . $this->version());
    }
}