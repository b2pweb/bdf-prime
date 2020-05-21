<?php

namespace Bdf\Prime\Migration\Console;

require_once __DIR__ . '/../_assets/CommandTestCase.php';

/**
 *
 */
class StatusCommandTest extends CommandTestCase
{
    /**
     *
     */
    public function test_execute()
    {
        $this->setVersions(['100', '101', '200', '300', '350']);

        $expected =
<<<TABLE
┌────────┬──────────────┬────────────────┬─────────┐
│ Status │ Migration ID │ Migration Name │ Stage   │
├────────┼──────────────┼────────────────┼─────────┤
│ up     │ 100          │ Migration100   │ default │
├────────┼──────────────┼────────────────┼─────────┤
│ up     │ 101          │ Migration101   │ prepare │
├────────┼──────────────┼────────────────┼─────────┤
│ up     │ 200          │ Migration200   │ default │
├────────┼──────────────┼────────────────┼─────────┤
│ up     │ 300          │ Migration300   │ default │
├────────┼──────────────┼────────────────┼─────────┤
│ down   │ 400          │ Migration400   │ default │
├────────┼──────────────┼────────────────┼─────────┤
│ down   │ 500          │ Migration500   │ default │
├────────┼──────────────┼────────────────┼─────────┤
│ down   │ 600          │ Migration600   │ prepare │
├────────┼──────────────┼────────────────┼─────────┤
│ up     │ 350          │ ** MISSING **  │         │
└────────┴──────────────┴────────────────┴─────────┘

TABLE
;

        $output = $this->execute(new StatusCommand($this->manager));

        $this->assertEquals($expected, $output);
    }

    /**
     *
     */
    public function test_execute_with_stage()
    {
        $this->setVersions(['101', '350']);

        $expected =
<<<TABLE
┌────────┬──────────────┬────────────────┬─────────┐
│ Status │ Migration ID │ Migration Name │ Stage   │
├────────┼──────────────┼────────────────┼─────────┤
│ up     │ 101          │ Migration101   │ prepare │
├────────┼──────────────┼────────────────┼─────────┤
│ down   │ 600          │ Migration600   │ prepare │
├────────┼──────────────┼────────────────┼─────────┤
│ up     │ 350          │ ** MISSING **  │         │
└────────┴──────────────┴────────────────┴─────────┘

TABLE
;

        $output = $this->execute(new StatusCommand($this->manager), ['--stage' => 'prepare']);

        $this->assertEquals($expected, $output);
    }
}