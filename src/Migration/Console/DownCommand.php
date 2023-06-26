<?php

namespace Bdf\Prime\Migration\Console;

use Bdf\Util\Console\BdfStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DownCommand
 */
#[AsCommand('prime:migration:down', 'Revert a specific migration')]
class DownCommand extends AbstractCommand
{
    protected static $defaultName = 'prime:migration:down';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Revert a specific migration')
            ->addArgument('version', InputArgument::REQUIRED, 'The version number for the migration')
            ->setHelp(
                <<<EOT
The <info>down</info> command reverts a specific migration

<info>down 20111018185412</info>

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

        return 0;
    }
}
