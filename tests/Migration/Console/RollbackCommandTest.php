<?php

namespace Bdf\Prime\Migration\Console;

require_once __DIR__ . '/../_assets/CommandTestCase.php';

/**
 *
 */
class RollbackCommandTest extends CommandTestCase
{
    /**
     *
     */
    public function test_execute()
    {
        $this->setVersions(['100', '200', '300']);

        $output = $this->execute(new RollbackCommand($this->manager), ['--target' => '100']);

        $this->assertRegExp(
            "/" .
                " == 300 Migration300 reverting\nInit 300\nDown 300\n == 300 Migration300 reverted [0-9\\.]+s\n" .
                " == 200 Migration200 reverting\nInit 200\nDown 200\n == 200 Migration200 reverted [0-9\\.]+s\n" .
            "/",
            $output
        );
        $this->assertEquals(['100'], $this->repository->all());
    }

    /**
     *
     */
    public function test_execute_with_no_specified_target()
    {
        $this->setVersions(['100', '200', '300']);

        $output = $this->execute(new RollbackCommand($this->manager));

        $this->assertRegExp(
            "/ == 300 Migration300 reverting\nInit 300\nDown 300\n == 300 Migration300 reverted [0-9\\.]+s\n/",
            $output
        );
        $this->assertEquals(['100', '200'], $this->repository->all());
    }

    /**
     *
     */
    public function test_execute_with_invalid_stage()
    {
        $this->setVersions(['100', '200', '300']);

        $output = $this->execute(new RollbackCommand($this->manager), ['--stage' => 'prepare']);

        $this->assertStringContainsString('Invalid stage. Expects "prepare" but get "default"', $output);
        $this->assertEquals(['100', '200', '300'], $this->repository->all());
    }

    /**
     *
     */
    public function test_execute_with_custom_stage()
    {
        $this->setVersions(['100', '101', '200', '600']);

        $output = $this->execute(new RollbackCommand($this->manager), ['--stage' => 'prepare', '--target' => '101']);

        $this->assertRegExp(
            "/ == 600 Migration600 reverting\nInit 600\nDown 600\n == 600 Migration600 reverted [0-9\\.]+s\n/",
            $output
        );
        $this->assertEquals(['100', '101', '200'], $this->repository->all());
    }

    /**
     *
     */
    public function test_execute_when_there_is_no_version_to_rollback()
    {
        $output = $this->execute(new RollbackCommand($this->manager), ['--target' => '100']);

        $this->assertStringContainsString("No migrations to rollback", $output);
        $this->assertEquals([], $this->repository->all());
    }

    /**
     *
     */
    public function test_execute_when_version_does_not_exist()
    {
        $this->setVersions(['100', '200', '300']);

        $output = $this->execute(new RollbackCommand($this->manager), ['--target' => '102']);

        $this->assertRegExp(
            "/" .
                " == 300 Migration300 reverting\nInit 300\nDown 300\n == 300 Migration300 reverted [0-9\\.]+s\n" .
                " == 200 Migration200 reverting\nInit 200\nDown 200\n == 200 Migration200 reverted [0-9\\.]+s\n" .
            "/",
            $output
        );
        $this->assertEquals(['100'], $this->repository->all());
    }
}