<?php

namespace Bdf\Prime\Migration\Console;

use Bdf\Prime\Migration\MigrationManager;
use Bdf\Prime\Migration\Provider\FileMigrationProvider;
use Bdf\Prime\Migration\Provider\MigrationFactory;
use Bdf\Prime\Migration\Version\DbVersionRepository;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class CommandTestCase extends TestCase
{
    use PrimeTestCase;

    protected $migrationTable = 'migration_table';
    protected $workingDir =  __DIR__.'/../_assets/migrations';

    /** @var FileMigrationProvider */
    protected $locator;
    /** @var DbVersionRepository */
    protected $repository;
    /** @var MigrationFactory */
    protected $factory;
    /** @var FileMigrationProvider */
    protected $provider;
    /** @var MigrationManager */
    protected $manager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->configurePrime();

        Prime::connection('test')->schema()->table($this->migrationTable, function($table) {
            $table->string('version');
        });

        $this->repository = new DbVersionRepository(Prime::connection('test'), $this->migrationTable);
        $this->factory = new MigrationFactory($this->createMock(ContainerInterface::class));
        $this->provider = new FileMigrationProvider($this->factory, $this->workingDir);
        $this->manager = new MigrationManager($this->repository, $this->provider);
    }

    protected function tearDown(): void
    {
        Prime::connection('test')->schema()->truncate($this->migrationTable);
    }

    /**
     * @param string|\Symfony\Component\Console\Command\Command $command
     * @param array $input
     * @param array $options
     *
     * @return false|string|string[]
     */
    public function execute($command, array $input = [], array $options = [])
    {
        if (!isset($options['verbosity'])) {
            $options['verbosity'] = OutputInterface::VERBOSITY_VERY_VERBOSE;
        }


        if ($command->getHelperSet() === null) {
            $command->setHelperSet(new HelperSet());
        }

        $tester = new CommandTester($command);

        if (isset($options['inputs'])) {
            $tester->setInputs((array)$options['inputs']);
        }

        $tester->execute($input, $options);

        return $tester->getDisplay();
    }

    /**
     * @param array $versions
     */
    protected function setVersions(array $versions)
    {
        foreach ($versions as $version) {
            Prime::connection('test')->insert($this->migrationTable, [
                'version' => $version,
            ]);
        }
    }
}
