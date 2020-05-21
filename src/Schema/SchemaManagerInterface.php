<?php
namespace Bdf\Prime\Schema;

use Bdf\Prime\Schema\Manager\DatabaseManagerInterface;
use Bdf\Prime\Schema\Manager\QueryManagerInterface;
use Bdf\Prime\Schema\Manager\TableManagerInterface;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Schema as DoctrineSchema;
use Doctrine\DBAL\Schema\Table as DoctrineTable;

/**
 * Schema manager
 *
 * @author seb
 */
interface SchemaManagerInterface extends DatabaseManagerInterface, TableManagerInterface, QueryManagerInterface
{
    /**
     * Get the doctrine schema instance
     *
     * @param DoctrineTable|DoctrineTable[]|TableInterface|TableInterface[] $tables
     *
     * @return DoctrineSchema
     *
     * @deprecated since 1.3 Doctrine schema should be used only internally
     */
    public function schema($tables = []);

    /**
     * Load the doctrine schema from the connection
     *
     * @return Schema
     *
     * @deprecated since 1.3 Doctrine schema should be used only internally
     */
    public function loadSchema();

    /**
     * Get the diff queries from two tables
     *
     * @param TableInterface $newTable
     * @param TableInterface $oldTable
     *
     * @return mixed
     *
     * @internal The return value depends of the platform. You should not rely on the return of this method
     */
    public function diff(TableInterface $newTable, TableInterface $oldTable);
}
