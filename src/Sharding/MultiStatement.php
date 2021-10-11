<?php

namespace Bdf\Prime\Sharding;

use Doctrine\DBAL\Driver\ResultStatement;

/**
 * Array cache statement
 * 
 * @deprecated Use MultiResult
 */
class MultiStatement implements \IteratorAggregate, ResultStatement
{
    /**
     * @var ResultStatement[]
     */
    protected $statements;

    /**
     * @var int
     */
    protected $current = 0;

    /**
     * @param array $statements
     */
    public function __construct(array $statements = [])
    {
        $this->statements = $statements;
    }

    /**
     * {@inheritdoc}
     */
    public function add(ResultStatement $statement)
    {
        $this->statements[] = $statement;
    }

    /**
     * {@inheritdoc}
     */
    public function closeCursor()
    {
        foreach ($this->statements as $statement) {
            $statement->closeCursor();
        }

        unset($this->statements);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function columnCount()
    {
        if (!isset($this->statements[0])) {
            return 0;
        }

        return $this->statements[0]->columnCount();
    }

    /**
     * {@inheritdoc}
     */
    public function setFetchMode($fetchMode, $arg2 = null, $arg3 = null)
    {
        foreach ($this->statements as $statement) {
            $statement->setFetchMode($fetchMode, $arg2, $arg3);
        }

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
        // Stop the fetch if there s no statement
        if (!isset($this->statements[$this->current])) {
            return false;
        }

        $result = $this->statements[$this->current]->fetch($fetchMode);

        if (!$result) {
            // go to the next statement
            $this->current++;
            return $this->fetch($fetchMode);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll($fetchMode = null, $fetchArgument = null, $ctorArgs = null)
    {
        $result = [];

        foreach ($this->statements as $statement) {
            $result = array_merge($result, $statement->fetchAll($fetchMode));
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($columnIndex = 0)
    {
        $result = [];

        foreach ($this->statements as $statement) {
            $result[] = $statement->fetchColumn($columnIndex);
        }

        //TODO change l'interface de la méthode !

        return $result;
    }
}
