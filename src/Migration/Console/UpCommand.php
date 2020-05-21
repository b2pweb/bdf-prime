<?php

namespace Bdf\Prime\Migration\Console;

use Bdf\Util\Console\BdfStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UpCommand
 */
class UpCommand extends AbstractCommand
{
    protected static $defaultName = 'prime:migration:up';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Run a specific migration')
            ->addArgument('version', InputArgument::REQUIRED, 'The version number for the migration')
            ->setHelp(<<<EOT
The <info>up</info> command runs a specific migration

<info>up 20111018185121</info>

EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new BdfStyle($input, $output);
        $version = $io->argument('version');
        $manager = $this->manager($input, $output);

        if ($manager->isUp($version)) {
            $io->debug("Version $version is found");
            return 0;
        }

        if (!$manager->hasMigration($version)) {
            $io->debug("Version $version has no migration file");
            return 0;
        }

        $manager->up($version);

        return 0;
    }
}
