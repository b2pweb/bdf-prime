<?php

namespace Bdf\Prime\Console;

use Bdf\Prime\Connection\ConnectionInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * 
 */
class DropDatabaseCommand extends DatabaseCommand
{
    protected static $defaultName = 'prime:database:drop';

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setDescription('Drops the database from the configuration')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force the database deletion.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function interactWithDatabase(ConnectionInterface $connection, ?string $dbName): void
    {
        if ($this->io->option('force') || $this->io->confirm("Would you like to drop database <comment>$dbName</comment>?")) {
            $connection->schema()->dropDatabase($dbName);

            $this->io->line('Database <comment>%s</comment> has been <info>dropped</info> for connection <comment>%s</comment>.', $dbName, $connection->getName());
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function interactWithNoDatabase(ConnectionInterface $connection, ?string $dbName): void
    {
        $this->io->line('Database <comment>%s</comment> for connection <comment>%s</comment> does not exist.', $dbName, $connection->getName());
    }
}