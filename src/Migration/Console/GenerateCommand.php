<?php

namespace Bdf\Prime\Migration\Console;

use Bdf\Prime\Migration\MigrationInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GenerateCommand
 */
#[AsCommand('prime:migration:generate', 'Generate a new migration')]
class GenerateCommand extends AbstractCommand
{
    protected static $defaultName = 'prime:migration:generate';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Generate a new migration')
            ->addArgument('name', InputArgument::REQUIRED, 'The name for the migration')
            ->addOption('stage', 's', InputOption::VALUE_REQUIRED, 'The migration stage', MigrationInterface::STAGE_DEFAULT)
            ->setHelp(
                <<<EOT
The <info>generate</info> command creates a new migration with the name and path specified 

<info>generate MyMigrationComponent ./migrations</info>

EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $this->manager($input, $output)->createMigration($input->getArgument('name'), $input->getOption('stage'));

        $output->writeln('<info>+f</info> ' . '.' . str_replace(getcwd(), '', $path));

        return 0;
    }
}
