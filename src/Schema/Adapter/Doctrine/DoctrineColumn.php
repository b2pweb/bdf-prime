<?php

namespace Bdf\Prime\Schema\Adapter\Doctrine;

use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Types\TypesRegistryInterface;
use Doctrine\DBAL\Schema\Column;

/**
 * Adapt doctrine column to prime column
 */
final class DoctrineColumn implements ColumnInterface
{
    /**
     * @var Column
     */
    private $column;

    /**
     * @var TypesRegistryInterface
     */
    private $types;


    /**
     * DoctrineColumn constructor.
     *
     * @param Column $column
     * @param TypesRegistryInterface $types
     */
    public function __construct(Column $column, TypesRegistryInterface $types)
    {
        $this->column = $column;
        $this->types = $types;
    }

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return $this->column->getName();
    }

    /**
     * {@inheritdoc}
     *
     * @todo Handle type mapping ?
     */
    public function type()
    {
        return $this->types->get($this->column->getType()->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function defaultValue()
    {
        return $this->column->getDefault();
    }

    /**
     * {@inheritdoc}
     */
    public function length()
    {
        return $this->column->getLength();
    }

    /**
     * {@inheritdoc}
     */
    public function autoIncrement()
    {
        return $this->column->getAutoincrement();
    }

    /**
     * {@inheritdoc}
     */
    public function unsigned()
    {
        return $this->column->getUnsigned();
    }

    /**
     * {@inheritdoc}
     */
    public function fixed()
    {
        return $this->column->getFixed();
    }

    /**
     * {@inheritdoc}
     */
    public function nillable()
    {
        return !$this->column->getNotnull();
    }

    /**
     * {@inheritdoc}
     */
    public function comment()
    {
        return $this->column->getComment();
    }

    /**
     * {@inheritdoc}
     */
    public function precision()
    {
        return $this->column->getPrecision();
    }

    /**
     * {@inheritdoc}
     */
    public function scale()
    {
        return $this->column->getScale();
    }

    /**
     * {@inheritdoc}
     */
    public function options()
    {
        return $this->column->getCustomSchemaOptions();
    }

    /**
     * {@inheritdoc}
     */
    public function option($name)
    {
        return $this->column->getCustomSchemaOption($name);
    }
}
