<?php

namespace Bdf\Prime\Schema;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Comparator as BaseComparator;
use Doctrine\DBAL\Schema\Table as BaseTable;
use Doctrine\DBAL\Schema\TableDiff;

/**
 * Schema comparator
 *
 * @package Bdf\Prime\Schema
 *
 * @internal Use {@see SchemaManager::diff()} instead
 */
class Comparator extends BaseComparator
{
    /**
     * Allow diff to list drop column
     *
     * @var bool
     */
    protected $listDropColumn = true;

    /**
     * Set flag that allowed diff to list drop columns
     *
     * @param bool $flag
     *
     * @return void
     */
    public function setListDropColumn($flag): void
    {
        $this->listDropColumn = (bool)$flag;
    }

    /**
     * {@inheritdoc}
     */
    public function compareTables(BaseTable $oldTable, BaseTable $newTable): TableDiff
    {
        $diff = parent::compareTables($oldTable, $newTable);

        if (!$this->listDropColumn) {
            /** @psalm-suppress InternalMethod */
            $diff = new TableDiff(
                $oldTable,
                addedColumns: $diff->getAddedColumns(),
                changedColumns: $diff->getChangedColumns(),
                droppedColumns: [],
                addedIndexes: $diff->getAddedIndexes(),
                droppedIndexes: $diff->getDroppedIndexes(),
                renamedIndexes: $diff->getRenamedIndexes(),
            );
        }

        return $diff;
    }

    /**
     * {@inheritdoc}
     */
    protected function columnsEqual(Column $column1, Column $column2): bool
    {
        // Doctrine always extract the collation from the database
        // But generally, prime doesn't explicitly define the collation,
        // so doctrine detect this as column change.
        // To mitigate this, we set to the column resolved from prime metadata the collation of
        // the column resolved from the database.
        $collation1 = $column1->getCollation();
        $collation2 = $column2->getCollation();

        if ($collation1 !== null && $collation2 === null) {
            $column2 = (clone $column2)->setPlatformOption('collation', $collation1);
        }

        if ($collation1 === null && $collation2 !== null) {
            $column1 = (clone $column1)->setPlatformOption('collation', $collation2);
        }

        return parent::columnsEqual($column1, $column2);
    }
}
