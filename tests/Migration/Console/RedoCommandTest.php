<?php

namespace Bdf\Prime\Migration\Console;

require_once __DIR__ . '/../_assets/CommandTestCase.php';

/**
 *
 */
class RedoCommandTest extends CommandTestCase
{
    /**
     *
     */
    public function test_execute()
    {
        $this->setVersions(['100']);

        $output = $this->execute(new RedoCommand($this->manager), ['version' => '100']);

        $this->assertMatchesRegularExpression(
            "/" .
                " == 100 Migration100 reverting\nInit 100\nDown 100\n == 100 Migration100 reverted [0-9\\.]+s\n" .
                " == 100 Migration100 migrating\nInit 100\nUp 100\n == 100 Migration100 migrated ([0-9\\.]*)s\n" .
            "/",
            $output
        );
        $this->assertEquals(['100'], $this->repository->all());
    }

    /**
     *
     */
    public function test_execute_when_version_is_not_found()
    {
        $output = $this->execute(new RedoCommand($this->manager), ['version' => '100']);

        $this->assertStringContainsString("Version 100 is not found", $output);
        $this->assertEquals([], $this->repository->all());
    }

    /**
     *
     */
    public function test_execute_when_migration_is_not_found()
    {
        $this->setVersions(['102']);

        $output = $this->execute(new RedoCommand($this->manager), ['version' => '102']);

        $this->assertStringContainsString("Version 102 has no migration file", $output);
        $this->assertEquals(['102'], $this->repository->all());
    }
}