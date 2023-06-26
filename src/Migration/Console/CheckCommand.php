<?php

namespace Bdf\Prime\Migration\Console;

use Bdf\Util\Console\BdfStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CheckCommand
 */
#[AsCommand('prime:migration:check', 'Check all migrations have been run, exit with non-zero if not')]
class CheckCommand extends AbstractCommand
{
    protected static $defaultName = 'prime:migration:check';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Check all migrations have been run, exit with non-zero if not')
            ->addOption('stage', 's', InputOption::VALUE_OPTIONAL, 'The migration stage. If not set, all stages will be dumped')
            ->setHelp(
                <<<EOT
The <info>check</info> checks that all migrations have been run and exits with a 
non-zero exit code if not, useful for build or deployment scripts.

<info>check</info>

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

        $downMigrations = $manager->getDownMigrations($io->option('stage'));

        if (empty($downMigrations)) {
            $io->info('Migration is up to date');
            return 0;
        }

        $headers = ['Status', 'Migration ID', 'Migration Name', 'Stage'];
        $info    = [];

        foreach ($downMigrations as $migration) {
            $info[] = ['<error>down</error>', $migration->version(), '<comment>' . $migration->name() . '</comment>', $migration->stage()];
            $info[] = new TableSeparator();
        }

        array_pop($info);
        $io->table($headers, $info);

        // Return error if down migrations have been found
        return 1;
    }
}
