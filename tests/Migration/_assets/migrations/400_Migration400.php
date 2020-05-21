<?php

use Bdf\Prime\Migration\Migration;

class Migration400 extends Migration
{
    /**
     * Initialize the migration
     */
    public function initialize()
    {
        $this->output->writeln('Init ' . $this->version());
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->output->writeln('Up ' . $this->version());
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->output->writeln('Down ' . $this->version());
    }
}