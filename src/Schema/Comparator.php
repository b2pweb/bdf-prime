<?php

namespace Bdf\Prime\Schema;

use Doctrine\DBAL\Schema\Comparator as BaseComparator;
use Doctrine\DBAL\Schema\Table as BaseTable;

/**
 * Schema comparator
 *
 * @package Bdf\Prime\Schema
 *
 * @deprecated since 1.3 Use Prime comparators instead
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
     */
    public function setListDropColumn($flag)
    {
        $this->listDropColumn = (bool)$flag;
    }
    
    /**
     * {@inheritdoc}
     */
    public function diffTable(BaseTable $table1, BaseTable $table2)
    {
        $diff = parent::diffTable($table1, $table2);
        
        if ($diff && !$this->listDropColumn) {
            $diff->removedColumns = [];
        }
        
        return $diff;
    }
}
