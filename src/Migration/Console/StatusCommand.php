<?php

namespace Bdf\Prime\Migration\Console;

use Bdf\Util\Console\BdfStyle;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class StatusCommand
 */
class StatusCommand extends AbstractCommand
{
    protected static $defaultName = 'prime:migration:status';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Show the up/down status of all migrations')
            ->addOption('stage', 's', InputOption::VALUE_OPTIONAL, 'The migration stage. If not set, all stages will be dumped')
            ->setHelp(
                <<<EOT
The <info>status</info> command prints a list of all migrations, along with their current status 

<info>status</info>

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
        $info = [];

        foreach ($manager->getMigrations($io->option('stage')) as $migration) {
            if ($manager->isUp($migration->version())) {
                $status = '<info>up</info>';
            } else {
                $status = '<error>down</error>';
            }

            $info[] = [$status, $migration->version(), '<comment>' . $migration->name() . '</comment>', $migration->stage()];
            $info[] = new TableSeparator();
        }

        foreach ($manager->getMissingMigrations() as $missingMigration) {
            $info[] = ['<error>up</error>', $missingMigration, '<error>** MISSING **</error>'];
            $info[] = new TableSeparator();
        }

        array_pop($info);
        $io->table(['Status', 'Migration ID', 'Migration Name', 'Stage'], $info);

        return 0;
    }
}
