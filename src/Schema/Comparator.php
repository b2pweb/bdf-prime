<?php

namespace Bdf\Prime\Schema;

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
    public function compareTables(BaseTable $fromTable, BaseTable $toTable): TableDiff
    {
        $diff = parent::compareTables($fromTable, $toTable);

        if (!$this->listDropColumn) {
            /** @psalm-suppress InternalProperty */
            $diff->removedColumns = [];
        }

        return $diff;
    }
}
