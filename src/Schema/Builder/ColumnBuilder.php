<?php

namespace Bdf\Prime\Schema\Builder;

use Bdf\Prime\Platform\PlatformTypeInterface;
use Bdf\Prime\Schema\Bag\Column;
use Bdf\Prime\Schema\IndexInterface;

/**
 * Class ColumnBuilder
 * Used internally by @see TableBuilder
 * This class must not be used (or declared) manually
 */
final class ColumnBuilder implements ColumnBuilderInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var PlatformTypeInterface
     */
    private $type;

    /**
     * @var mixed
     */
    private $defaultValue;

    /**
     * @var int
     */
    private $length;

    /**
     * @var bool
     */
    private $autoIncrement = false;

    /**
     * @var bool
     */
    private $unsigned = false;

    /**
     * @var bool
     */
    private $fixed = false;

    /**
     * @var bool
     */
    private $nillable = false;

    /**
     * @var string
     */
    private $comment;

    /**
     * @var int
     */
    private $precision;

    /**
     * @var int
     */
    private $scale;

    /**
     * @var string[]
     */
    private $indexes = [];

    /**
     * @var array
     */
    private $options = [];


    /**
     * ColumnBuilder constructor.
     *
     * @param string $name
     * @param PlatformTypeInterface $type
     * @param array $options
     */
    public function __construct($name, PlatformTypeInterface $type, array $options = [])
    {
        $this->name = $name;
        $this->type = $type;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function autoincrement($flag = true)
    {
        $this->autoIncrement = $flag;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function length($length)
    {
        $this->length = $length;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function comment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefault($value)
    {
        $this->defaultValue = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function precision($precision, $scale = 0)
    {
        $this->precision = $precision;
        $this->scale     = $scale;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function nillable($flag = true)
    {
        $this->nillable = $flag;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function unsigned($flag = true)
    {
        $this->unsigned = $flag;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function unique($index = true)
    {
        if (is_string($index)) {
            $this->indexes[$index] = IndexInterface::TYPE_UNIQUE;
        } else {
            $this->indexes[] = IndexInterface::TYPE_UNIQUE;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function fixed($flag = true)
    {
        $this->fixed = $flag;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function name($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function options(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function type(PlatformTypeInterface $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function build()
    {
        return new Column(
            $this->name,
            $this->type,
            // #16653 : The value is converted to DB value, because the defaultValue is a PHP value and may be imcompatible with DB value (boolean is an integer on SQL)
            $this->type->toDatabase($this->defaultValue),
            $this->length,
            $this->autoIncrement,
            $this->unsigned,
            $this->fixed,
            $this->nillable,
            $this->comment,
            $this->precision,
            $this->scale,
            $this->options
        );
    }

    /**
     * {@inheritdoc}
     */
    public function indexes()
    {
        return $this->indexes;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }
}
