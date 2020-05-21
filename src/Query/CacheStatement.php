<?php

namespace Bdf\Prime\Query;

use Doctrine\DBAL\Driver\ResultStatement;
use PDO;

/**
 * Array cache statement
 * 
 * @package Bdf\Prime\Query
 */
class CacheStatement implements \IteratorAggregate, ResultStatement
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var integer
     */
    protected $columnCount = 0;

    /**
     * @var integer
     */
    protected $num = 0;

    /**
     * @var integer
     */
    protected $defaultFetchMode = PDO::FETCH_BOTH;


    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        
        if (isset($data[0])) {
            $this->columnCount = count($data[0]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function closeCursor()
    {
        unset($this->data);
        $this->columnCount = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function columnCount()
    {
        return $this->columnCount;
    }

    /**
     * {@inheritdoc}
     */
    public function setFetchMode($fetchMode, $arg2 = null, $arg3 = null)
    {
        if ($arg2 !== null || $arg3 !== null) {
            throw new \InvalidArgumentException("Caching layer does not support 2nd/3rd argument to setFetchMode()");
        }

        $this->defaultFetchMode = $fetchMode;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->fetchAll());
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($fetchMode = null, $cursorOrientation = \PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
    {
        if (isset($this->data[$this->num])) {
            $row       = $this->data[$this->num++];
            $fetchMode = $fetchMode ?: $this->defaultFetchMode;
            
            if ($fetchMode === PDO::FETCH_ASSOC) {
                return $row;
            } elseif ($fetchMode === PDO::FETCH_NUM) {
                return array_values($row);
            } elseif ($fetchMode === PDO::FETCH_BOTH) {
                return array_merge($row, array_values($row));
            } elseif ($fetchMode === PDO::FETCH_COLUMN) {
                return reset($row);
            } else {
                throw new \InvalidArgumentException("Invalid fetch-style given for fetching result.");
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll($fetchMode = null, $fetchArgument = null, $ctorArgs = null)
    {
        $fetchMode = $fetchMode ?: $this->defaultFetchMode;
            
        if ($fetchMode === PDO::FETCH_ASSOC) {
            return $this->data;
        }
        
        $rows = array();
        
        while ($row = $this->fetch($fetchMode)) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($columnIndex = 0)
    {
        $row = $this->fetch(PDO::FETCH_NUM);
        if (!isset($row[$columnIndex])) {
            // TODO: verify this is correct behavior
            return false;
        }

        return $row[$columnIndex];
    }
}
