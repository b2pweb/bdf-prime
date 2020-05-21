<?php

namespace Bdf\Prime\Migration\Console;

require_once __DIR__ . '/../_assets/CommandTestCase.php';

/**
 *
 */
class CheckCommandTest extends CommandTestCase
{
    /**
     *
     */
    public function test_execute()
    {
        $this->setVersions(['100', '200', '400']);

        $expected =
<<<TABLE
┌────────┬──────────────┬────────────────┬─────────┐
│ Status │ Migration ID │ Migration Name │ Stage   │
├────────┼──────────────┼────────────────┼─────────┤
│ down   │ 101          │ Migration101   │ prepare │
├────────┼──────────────┼────────────────┼─────────┤
│ down   │ 300          │ Migration300   │ default │
├────────┼──────────────┼────────────────┼─────────┤
│ down   │ 500          │ Migration500   │ default │
├────────┼──────────────┼────────────────┼─────────┤
│ down   │ 600          │ Migration600   │ prepare │
└────────┴──────────────┴────────────────┴─────────┘

TABLE
;

        $output = $this->execute(new CheckCommand($this->manager));

        $this->assertEquals($expected, $output);
    }
    /**
     *
     */
    public function test_execute_with_stage()
    {
        $this->setVersions(['100']);

        $expected =
<<<TABLE
┌────────┬──────────────┬────────────────┬─────────┐
│ Status │ Migration ID │ Migration Name │ Stage   │
├────────┼──────────────┼────────────────┼─────────┤
│ down   │ 101          │ Migration101   │ prepare │
├────────┼──────────────┼────────────────┼─────────┤
│ down   │ 600          │ Migration600   │ prepare │
└────────┴──────────────┴────────────────┴─────────┘

TABLE
;

        $output = $this->execute(new CheckCommand($this->manager), ['--stage' => 'prepare']);

        $this->assertEquals($expected, $output);
    }

    /**
     *
     */
    public function test_execute_when_migration_is_up_to_date()
    {
        $this->setVersions(['100', '101', '200', '300', '350', '400', '500', '600']);

        $output = $this->execute(new CheckCommand($this->manager));
        $this->assertEquals("Migration is up to date\n", $output);
    }
}