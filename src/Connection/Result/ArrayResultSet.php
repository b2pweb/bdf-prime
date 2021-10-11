<?php

namespace Bdf\Prime\Connection\Result;

use Bdf\Prime\Exception\DBALException;

/**
 * Wrap simple associative array to ResultSet
 * This result is usefull for caches
 */
final class ArrayResultSet extends \ArrayIterator implements ResultSetInterface
{
    /**
     * @var string
     */
    private $fetchMode = self::FETCH_ASSOC;

    /**
     * @var mixed
     */
    private $fetchOptions;

    /**
     * @var string[]
     */
    private $columns;

    /**
     * @var \ReflectionClass
     */
    private $reflectionClass;

    /**
     * @var \ReflectionProperty[]
     */
    private $reflectionProperties;


    /**
     * {@inheritdoc}
     */
    public function fetchMode($mode, $options = null)
    {
        $this->fetchMode = $mode;
        $this->fetchOptions = $options;

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
        return $this->fetchMode === self::FETCH_ASSOC
            ? $this->getArrayCopy()
            : array_map([$this, 'fetchValue'], $this->getArrayCopy())
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->fetchValue(parent::current());
    }

    /**
     * Transform the value according to the fetch mode
     *
     * @param array $current
     *
     * @return mixed
     */
    private function fetchValue($current)
    {
        switch ($this->fetchMode) {
            case self::FETCH_ASSOC:
                return $current;

            case self::FETCH_NUM:
                return array_values($current);

            case self::FETCH_COLUMN:
                return $this->fetchColum($current);

            case self::FETCH_OBJECT:
                return (object) $current;

            case self::FETCH_CLASS:
                return $this->fetchClass($current);

            default:
                throw new DBALException('Unsupported fetch mode '.$this->fetchMode);
        }
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

    private function fetchColum($current)
    {
        if (!$this->fetchOptions) {
            return reset($current);
        }

        if (!$this->columns) {
            $this->columns = array_keys($current);
        }

        return $current[$this->columns[$this->fetchOptions]];
    }

    private function fetchClass($current)
    {
        if (!$this->reflectionClass) {
            $this->reflectionClass = new \ReflectionClass($this->fetchOptions);
        }

        $object = $this->reflectionClass->newInstance();

        foreach ($current as $property => $value) {
            if (!isset($this->reflectionProperties[$property])) {
                $this->reflectionProperties[$property] = $this->reflectionClass->getProperty($property);
                $this->reflectionProperties[$property]->setAccessible(true);
            }

            $this->reflectionProperties[$property]->setValue($object, $value);
        }

        return $object;
    }
}
