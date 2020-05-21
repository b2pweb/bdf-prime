<?php

namespace Bdf\Prime\Migration\Console;

use Bdf\Prime\Migration\MigrationInterface;
use Bdf\Util\Console\BdfStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MigrateCommand
 */
class MigrateCommand extends AbstractCommand
{
    protected static $defaultName = 'prime:migration:migrate';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Run all migrations')
            ->addOption('--target', '-t', InputArgument::OPTIONAL, 'The version number to migrate to')
            ->addOption('stage', 's', InputOption::VALUE_REQUIRED, 'The migration stage', MigrationInterface::STAGE_DEFAULT)
            ->setHelp(<<<EOT
The <info>migrate</info> command runs all available migrations, optionally up to a specific version

<info>migrate</info>
<info>migrate -t 20111018185412</info>

EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new BdfStyle($input, $output);
        $manager = $this->manager($input, $output);

        $version = $io->option('target');
        $stage   = $io->option('stage');

        if (null !== $version) {
            if (!$manager->hasMigration($version, $stage)) {
                $io->error('Version "'.$version.'" has not been found. No migrations to migrate');
                return 1;
            }
        } else {
            $version = $manager->getMostRecentVersion($stage);

            if (empty($version)) {
                $io->line('No migrations to migrate');
                return 0;
            }
        }

        if ($manager->isOlder($version)) {
            $manager->rollback($version, $stage);
        } else {
            $manager->migrate($version, $stage);
        }

        return 0;
    }
}
