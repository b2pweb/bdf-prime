<?php

namespace Bdf\Prime\Migration\Console;

use Bdf\Prime\Migration\MigrationInterface;
use Bdf\Util\Console\BdfStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RollbackCommand
 */
class RollbackCommand extends AbstractCommand
{
    protected static $defaultName = 'prime:migration:rollback';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Rollback last, or to a specific migration')
            ->addOption('--target', '-t', InputArgument::OPTIONAL, 'The version number to rollback to')
            ->addOption('stage', 's', InputOption::VALUE_REQUIRED, 'The migration stage', MigrationInterface::STAGE_DEFAULT)
            ->setHelp(<<<EOT
The <info>rollback</info> command reverts the last migration, or optionally up to a specific version

<info>rollback</info>
<info>rollback -t 20111018185412</info>

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

        $version        = $io->option('target');
        $versions       = $manager->getVersions();
        $currentVersion = $manager->getCurrentVersion();

        // Check we have at least 1 migration to revert
        if (empty($versions)) {
            $io->error('No migrations to rollback');
            return 1;
        }

        // If no target version was supplied, revert the last migration
        if (null === $version || $version == $currentVersion) {
            $migration = $manager->getMigration($currentVersion);

            if ($migration->stage() !== $io->option('stage')) {
                $io->error('Invalid stage. Expects "%s" but get "%s"', $io->option('stage'), $migration->stage());
                return 1;
            }

            $manager->down($currentVersion);
            return 0;
        }

        $manager->rollback($version, $io->option('stage'));

        return 0;
    }
}
