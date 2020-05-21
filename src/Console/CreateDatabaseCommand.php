<?php

namespace Bdf\Prime\Console;

/**
 * 
 */
class CreateDatabaseCommand extends DatabaseCommand
{
    protected static $defaultName = 'prime:database:create';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setDescription('Creates the database from the configuration')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function interactWithDatabase($connection, $dbName)
    {
        $this->io->line('Database <comment>%s</comment> for connection <comment>%s</comment> already exists.', $dbName, $connection->getName());
    }

    /**
     * {@inheritdoc}
     */
    protected function interactWithNoDatabase($connection, $dbName)
    {
        $connection->schema()->createDatabase($dbName);

        $this->io->line('Database <comment>%s</comment> has been <info>created</info> for connection <comment>%s</comment>.', $dbName, $connection->getName());
    }
}
