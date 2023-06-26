<?php

namespace Bdf\Prime\Migration\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class InitCommand
 */
#[AsCommand('prime:migration:init', 'Initialise this directory for use with migration')]
class InitCommand extends AbstractCommand
{
    protected static $defaultName = 'prime:migration:init';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Initialise this directory for use with migration')
            ->setHelp(
                <<<EOT
The <info>init</info> command creates a migrations directory

<info>init</info>

EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->manager($input, $output)->initMigrationRepository();

        $output->writeln(
            '<comment>Place your migration files in</comment> ' .
            '<info>' . str_replace(getcwd(), '.', realpath($this->manager($input, $output)->getMigrationPath())) . '</info>'
        );

        return 0;
    }
}
