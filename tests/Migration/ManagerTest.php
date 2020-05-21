<?php

namespace Bdf\Prime\Migration;

use Bdf\Prime\Migration\Provider\FileMigrationProvider;
use Bdf\Prime\Migration\Provider\MigrationFactory;
use Bdf\Prime\Migration\Version\DbVersionRepository;
use Bdf\Prime\Prime;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 *
 */
class ManagerTest extends TestCase
{
    /** @var string */
    private $migrationTable = 'migration_table';

    /** @var BufferedOutput */
    private $output;

    /** @var ContainerInterface */
    private $di;

    /** @var MigrationManager */
    private $workflow;

    /** @var DbVersionRepository */
    private $repository;

    /** @var FileMigrationProvider */
    private $provider;

    /**
     * 
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->output = new BufferedOutput();
        $this->di = $this->createMock(ContainerInterface::class);

        $this->workflow = new MigrationManager(
            $this->repository = new DbVersionRepository(Prime::connection('test'), $this->migrationTable),
            $this->provider = new FileMigrationProvider(new MigrationFactory($this->di), __DIR__ . '/_assets/migrations')
        );
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->repository->hasSchema()) {
            Prime::connection('test')->schema()->truncate($this->migrationTable);
        }
    }

    /**
     *
     */
    public function test_hasMigration()
    {
        $this->assertTrue($this->workflow->hasMigration('100'), 'Failed asserting that manager has migration 100');
        $this->assertFalse($this->workflow->hasMigration('100', Migration::STAGE_PREPARE), 'Failed asserting that manager has migration 100 stage prepare');
        $this->assertTrue($this->workflow->hasMigration('101', Migration::STAGE_PREPARE), 'Failed asserting that manager has migration 101 stage prepare');
        $this->assertFalse($this->workflow->hasMigration('150'), 'Failed asserting that manager has not migration 150');
    }

    /**
     *
     */
    public function test_getMigrations()
    {
        $this->assertEquals([
            '100' => new \Migration100('100', $this->di),
            '101' => new \Migration101('101', $this->di),
            '200' => new \Migration200('200', $this->di),
            '300' => new \Migration300('300', $this->di),
            '400' => new \Migration400('400', $this->di),
            '500' => new \Migration500('500', $this->di),
            '600' => new \Migration600('600', $this->di),
        ], $this->workflow->getMigrations());
    }

    /**
     *
     */
    public function test_getMigrations_with_stage()
    {
        $this->assertEquals([
            '101' => new \Migration101('101', $this->di),
            '600' => new \Migration600('600', $this->di),
        ], $this->workflow->getMigrations('prepare'));
    }

    /**
     *
     */
    public function test_getDownMigrations()
    {
        $this->repository->add('100');
        $this->repository->add('200');
        $this->repository->add('300');

        $this->assertEquals([
            '101' => new \Migration101('101', $this->di),
            '400' => new \Migration400('400', $this->di),
            '500' => new \Migration500('500', $this->di),
            '600' => new \Migration600('600', $this->di),
        ], $this->workflow->getDownMigrations());
    }

    /**
     *
     */
    public function test_getDownMigrations_with_stage()
    {
        $this->repository->add('100');
        $this->repository->add('200');
        $this->repository->add('300');

        $this->assertEquals([
            '400' => new \Migration400('400', $this->di),
            '500' => new \Migration500('500', $this->di),
        ], $this->workflow->getDownMigrations('default'));
    }

    /**
     *
     */
    public function test_getMostRecentVersion()
    {
        $this->assertEquals('500', $this->workflow->getMostRecentVersion('default'));
        $this->assertEquals('600', $this->workflow->getMostRecentVersion('prepare'));

        $this->repository->add('650');
        $this->assertEquals('650', $this->workflow->getMostRecentVersion('prepare'));
        $this->assertEquals('650', $this->workflow->getMostRecentVersion('default'));
    }

    /**
     *
     */
    public function test_isUp()
    {
        $this->assertFalse($this->workflow->isUp('100'), 'Failed asserting that manager has not version 100');

        $this->repository->add('100');
        $this->assertTrue($this->workflow->isUp('100'), 'Failed asserting that manager has version 100');
    }

    /**
     *
     */
    public function test_up()
    {
        $this->repository->add('200');

        $this->workflow->setOutput($this->output);
        $this->workflow->up('100');

        $this->assertRegExp("/ == 100 Migration100 migrating\nInit 100\nUp 100\n == 100 Migration100 migrated [0-9\\.]+s\n/", $this->output->fetch());
        $this->assertEquals([100, 200], $this->repository->all());
    }

    /**
     *
     */
    public function test_down()
    {
        $this->repository->add('100');
        $this->repository->add('200');
        $this->repository->add('300');

        $this->workflow->setOutput($this->output);
        $this->workflow->down('200');

        $this->assertRegExp("/ == 200 Migration200 reverting\nInit 200\nDown 200\n == 200 Migration200 reverted [0-9\\.]+s\n/", $this->output->fetch());
        $this->assertEquals([100, 300], $this->repository->all());
    }

    /**
     *
     */
    public function test_migrate()
    {
        $this->repository->add('200');

        $this->workflow->setOutput($this->output);
        $this->workflow->migrate('300');

        $this->assertRegExp(
            "/" .
            " == 100 Migration100 migrating\nInit 100\nUp 100\n == 100 Migration100 migrated [0-9\\.]+s\n" .
            " == 300 Migration300 migrating\nInit 300\nUp 300\n == 300 Migration300 migrated [0-9\\.]+s\n" .
            "/",
            $this->output->fetch()
        );
        $this->assertEquals([100, 200, 300], $this->repository->all());
    }

    /**
     *
     */
    public function test_migrate_with_stage()
    {
        $this->workflow->setOutput($this->output);
        $this->workflow->migrate('600', Migration::STAGE_PREPARE);

        $this->assertRegExp(
            "/" .
            " == 101 Migration101 migrating\nInit 101\nUp 101\n == 101 Migration101 migrated [0-9\\.]+s\n" .
            " == 600 Migration600 migrating\nInit 600\nUp 600\n == 600 Migration600 migrated [0-9\\.]+s\n" .
            "/",
            $this->output->fetch()
        );
        $this->assertEquals(['101', '600'], $this->repository->all());
    }

    /**
     *
     */
    public function test_rollback()
    {
        $this->repository->add('100');
        $this->repository->add('200');
        $this->repository->add('300');
        $this->repository->add('500');

        $this->workflow->setOutput($this->output);
        $this->workflow->rollback('200');

        $this->assertRegExp(
            "/" .
            " == 500 Migration500 reverting\nInit 500\nDown 500\n == 500 Migration500 reverted [0-9\\.]+s\n" .
            " == 300 Migration300 reverting\nInit 300\nDown 300\n == 300 Migration300 reverted [0-9\\.]+s\n" .
            "/",
            $this->output->fetch()
        );
        $this->assertEquals(['100', '200'], $this->repository->all());
    }

    /**
     *
     */
    public function test_rollback_with_stage()
    {
        $this->repository->add('100');
        $this->repository->add('101');
        $this->repository->add('500');
        $this->repository->add('600');

        $this->workflow->setOutput($this->output);
        $this->workflow->rollback('100', Migration::STAGE_PREPARE);

        $this->assertRegExp(
            "/" .
            " == 600 Migration600 reverting\nInit 600\nDown 600\n == 600 Migration600 reverted [0-9\\.]+s\n" .
            " == 101 Migration101 reverting\nInit 101\nDown 101\n == 101 Migration101 reverted [0-9\\.]+s\n" .
            "/",
            $this->output->fetch()
        );
        $this->assertEquals(['100', '500'], $this->repository->all());
    }
}
