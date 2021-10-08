<?php

namespace Bdf\Prime\Migration\Console;

require_once __DIR__ . '/../_assets/CommandTestCase.php';

/**
 *
 */
class MigrateCommandTest extends CommandTestCase
{
    /**
     *
     */
    public function test_execute_in_full_upgrading_context()
    {
        $this->setVersions(['100']);

        $output = $this->execute(new MigrateCommand($this->manager));

        $this->assertMatchesRegularExpression(
            "/" .
                " == 200 Migration200 migrating\nInit 200\nUp 200\n == 200 Migration200 migrated [0-9\\.]+s\n" .
                " == 300 Migration300 migrating\nInit 300\nUp 300\n == 300 Migration300 migrated [0-9\\.]+s\n" .
                " == 400 Migration400 migrating\nInit 400\nUp 400\n == 400 Migration400 migrated [0-9\\.]+s\n" .
                " == 500 Migration500 migrating\nInit 500\nUp 500\n == 500 Migration500 migrated [0-9\\.]+s\n" .
            "/",
            $output
        );
        $this->assertEquals(['100', '200', '300', '400', '500'], $this->repository->all());
    }

    /**
     *
     */
    public function test_execute_upgrade_custom_stage()
    {
        $this->setVersions(['100']);

        $output = $this->execute(new MigrateCommand($this->manager), ['--stage' => 'prepare']);

        $this->assertMatchesRegularExpression(
            "/" .
                " == 101 Migration101 migrating\nInit 101\nUp 101\n == 101 Migration101 migrated [0-9\\.]+s\n" .
                " == 600 Migration600 migrating\nInit 600\nUp 600\n == 600 Migration600 migrated [0-9\\.]+s\n" .
            "/",
            $output
        );
        $this->assertEquals(['100', '101', '600'], $this->repository->all());
    }

    /**
     *
     */
    public function test_execute_with_an_unknown_version_number()
    {
        $this->setVersions(['100']);

        $output = $this->execute(new MigrateCommand($this->manager), ['--target' => '350']);

        $this->assertMatchesRegularExpression(
            '/Version "350" has not been found/',
            $output
        );
    }

    /**
     *
     */
    public function test_execute_in_upgrading_context()
    {
        $this->setVersions(['100']);

        $output = $this->execute(new MigrateCommand($this->manager), ['--target' => '300']);

        $this->assertMatchesRegularExpression(
            "/" .
                " == 200 Migration200 migrating\nInit 200\nUp 200\n == 200 Migration200 migrated [0-9\\.]+s\n" .
                " == 300 Migration300 migrating\nInit 300\nUp 300\n == 300 Migration300 migrated [0-9\\.]+s\n" .
            "/",
            $output
        );
        $this->assertEquals(['100', '200', '300'], $this->repository->all());
    }

    /**
     *
     */
    public function test_execute_in_downgrading_context()
    {
        $this->setVersions(['100', '200', '300', '400']);

        $output = $this->execute(new MigrateCommand($this->manager), ['--target' => '200']);

        $this->assertMatchesRegularExpression(
            "/" .
                " == 400 Migration400 reverting\nInit 400\nDown 400\n == 400 Migration400 reverted [0-9\\.]+s\n" .
                " == 300 Migration300 reverting\nInit 300\nDown 300\n == 300 Migration300 reverted [0-9\\.]+s\n" .
            "/",
            $output
        );
        $this->assertEquals(['100', '200'], $this->repository->all());
    }

    /**
     *
     */
    public function test_execute_downgrading_with_custom_stage()
    {
        $this->setVersions(['100', '101', '200', '600']);

        $output = $this->execute(new MigrateCommand($this->manager), ['--target' => '101', '--stage' => 'prepare']);

        $this->assertMatchesRegularExpression(
            "/" .
                " == 600 Migration600 reverting\nInit 600\nDown 600\n == 600 Migration600 reverted [0-9\\.]+s\n" .
            "/",
            $output
        );
        $this->assertEquals(['100', '101', '200'], $this->repository->all());
    }

    /**
     *
     */
    public function test_execute_with_no_migration_required()
    {
        $this->setVersions(['100', '200', '300']);

        $output = $this->execute(new MigrateCommand($this->manager), ['--target' => '300']);

        $this->assertEquals('', $output);
        $this->assertEquals(['100', '200', '300'], $this->repository->all());
    }
}