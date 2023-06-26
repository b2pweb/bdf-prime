<?php

namespace Bdf\Prime\Migration\Console;

use Bdf\Util\Console\BdfStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RedoCommand
 */
#[AsCommand('prime:migration:redo', 'Redo a specific migration')]
class RedoCommand extends AbstractCommand
{
    protected static $defaultName = 'prime:migration:redo';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Redo a specific migration')
            ->addArgument('version', InputArgument::REQUIRED, 'The version number for the migration')
            ->setHelp(
                <<<EOT
The <info>redo</info> command redo a specific migration

<info>redo 20111018185412</info>

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

        $version = $io->argument('version');

        if (!$manager->isUp($version)) {
            $io->debug("Version $version is not found");
            return 0;
        }

        if (!$manager->hasMigration($version)) {
            $io->debug("Version $version has no migration file");
            return 0;
        }

        $manager->down($version);
        $manager->up($version);

        return 0;
    }
}
