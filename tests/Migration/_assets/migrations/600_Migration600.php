<?php

use Bdf\Prime\Migration\Migration;

class Migration600 extends Migration
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

    public function stage()
    {
        return self::STAGE_PREPARE;
    }
}
