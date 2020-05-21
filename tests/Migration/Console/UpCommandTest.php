<?php

namespace Bdf\Prime\Migration\Console;

require_once __DIR__ . '/../_assets/CommandTestCase.php';

/**
 *
 */
class UpCommandTest extends CommandTestCase
{
    /**
     *
     */
    public function test_execute()
    {
        $output = $this->execute(new UpCommand($this->manager), ['version' => '100']);

        $this->assertRegExp("/ == 100 Migration100 migrating\nInit 100\nUp 100\n == 100 Migration100 migrated [0-9\\.]+s\n/", $output);
        $this->assertEquals(['100'], $this->repository->all());
    }

    /**
     *
     */
    public function test_execute_when_version_is_found()
    {
        $this->setVersions(['100']);

        $output = $this->execute(new UpCommand($this->manager), ['version' => '100']);

        $this->assertStringContainsString("Version 100 is found", $output);
        $this->assertEquals(['100'], $this->repository->all());
    }

    /**
     *
     */
    public function test_execute_when_migration_is_not_found()
    {
        $output = $this->execute(new UpCommand($this->manager), ['version' => '102']);

        $this->assertStringContainsString("Version 102 has no migration file", $output);
        $this->assertEquals([], $this->repository->all());
    }
}