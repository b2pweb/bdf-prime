<?php

namespace Bdf\Prime\Console;

use Bdf\Prime\Connection\ConnectionInterface;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand('prime:database:create', 'Creates the database from the configuration')]
class CreateDatabaseCommand extends DatabaseCommand
{
    protected static $defaultName = 'prime:database:create';

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setDescription('Creates the database from the configuration')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function interactWithDatabase(ConnectionInterface $connection, ?string $dbName): void
    {
        $this->io->line('Database <comment>%s</comment> for connection <comment>%s</comment> already exists.', $dbName, $connection->getName());
    }

    /**
     * {@inheritdoc}
     */
    protected function interactWithNoDatabase(ConnectionInterface $connection, ?string $dbName): void
    {
        $connection->schema()->createDatabase($dbName);

        $this->io->line('Database <comment>%s</comment> has been <info>created</info> for connection <comment>%s</comment>.', $dbName, $connection->getName());
    }
}
