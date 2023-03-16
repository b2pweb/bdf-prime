<?php

namespace Bdf\Prime\Schema;

use Bdf\Prime\Schema\Manager\DatabaseManagerInterface;
use Bdf\Prime\Schema\Manager\QueryManagerInterface;
use Bdf\Prime\Schema\Manager\RollbackQueryManagerInterface;
use Bdf\Prime\Schema\Manager\TableManagerInterface;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Schema as DoctrineSchema;
use Doctrine\DBAL\Schema\Table as DoctrineTable;

/**
 * Schema manager
 *
 * @template C as \Bdf\Prime\Connection\ConnectionInterface
 * @extends DatabaseManagerInterface<C>
 */
interface SchemaManagerInterface extends DatabaseManagerInterface, TableManagerInterface, QueryManagerInterface, RollbackQueryManagerInterface
{
    /**
     * Get the doctrine schema instance
     *
     * @param DoctrineTable|DoctrineTable[]|TableInterface|TableInterface[] $tables
     *
     * @return DoctrineSchema
     *
     * @internal Doctrine schema should be used only internally
     */
    public function schema($tables = []);

    /**
     * Load the doctrine schema from the connection
     *
     * @return Schema
     *
     * @internal Doctrine schema should be used only internally
     */
    public function loadSchema();
}
