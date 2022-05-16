<?php

namespace Bdf\Prime\Connection\Result;

use Bdf\Prime\Exception\DBALException;
use Doctrine\DBAL\Driver\Statement;

/**
 * Adapt PDO statement to result set
 *
 * @deprecated Use DoctrineResultSet
 */
/*final*/ class PdoResultSet implements ResultSetInterface
{
    /**
     * @var Statement|\PDOStatement
     */
    private $statement;

    /**
     * @var int
     */
    private $key = 0;

    /**
     * @var mixed
     */
    private $current;


    /**
     * PdoResultSet constructor.
     *
     * @param Statement|\PDOStatement $statement
     */
    public function __construct($statement)
    {
        $this->statement = $statement;
        $this->statement->setFetchMode(\PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        if ($this->current === null) {
            $this->rewind();
        }

        return $this->current;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->current = $this->statement->fetch();
        ++$this->key;
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress InvalidReturnStatement
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->current !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchMode($mode, $options = null)
    {
        switch ($mode) {
            case self::FETCH_ASSOC:
                $this->statement->setFetchMode(\PDO::FETCH_ASSOC);
                break;

            case self::FETCH_NUM:
                $this->statement->setFetchMode(\PDO::FETCH_NUM);
                break;

            case self::FETCH_OBJECT:
                $this->statement->setFetchMode(\PDO::FETCH_OBJ);
                break;

            case self::FETCH_COLUMN:
                $this->statement->setFetchMode(\PDO::FETCH_COLUMN, $options ?: 0);
                break;

            case self::FETCH_CLASS:
                $this->statement->setFetchMode(\PDO::FETCH_CLASS, (string) $options);
                break;

            default:
                throw new DBALException('Unsupported fetch mode '.$mode);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function asAssociative(): ResultSetInterface
    {
        return $this->fetchMode(self::FETCH_ASSOC);
    }

    /**
     * {@inheritdoc}
     */
    public function asList(): ResultSetInterface
    {
        return $this->fetchMode(self::FETCH_NUM);
    }

    /**
     * {@inheritdoc}
     */
    public function asObject(): ResultSetInterface
    {
        return $this->fetchMode(self::FETCH_OBJECT);
    }

    /**
     * {@inheritdoc}
     */
    public function asClass(string $className): ResultSetInterface
    {
        return $this->fetchMode(self::FETCH_CLASS, $className);
    }

    /**
     * {@inheritdoc}
     */
    public function asColumn(int $column = 0): ResultSetInterface
    {
        return $this->fetchMode(self::FETCH_COLUMN, $column);
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        return $this->statement->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->current = $this->statement->fetch();
        $this->key = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return (int) $this->statement->rowCount();
    }

    /**
     * {@inheritdoc}
     */
    public function isRead(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isWrite(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function hasWrite(): bool
    {
        return false;
    }

    /**
     * Close the cursor on result set destruction
     */
    public function __destruct()
    {
        $this->statement->closeCursor();
    }
}
