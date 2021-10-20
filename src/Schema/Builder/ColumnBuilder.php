<?php

namespace Bdf\Prime\Schema\Builder;

use Bdf\Prime\Platform\PlatformTypeInterface;
use Bdf\Prime\Schema\Bag\Column;
use Bdf\Prime\Schema\ColumnInterface;
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
     * @var int|null
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
     * @var string|null
     */
    private $comment;

    /**
     * @var int|null
     */
    private $precision;

    /**
     * @var int|null
     */
    private $scale;

    /**
     * @var IndexInterface::TYPE_*[]
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
    public function __construct(string $name, PlatformTypeInterface $type, array $options = [])
    {
        $this->name = $name;
        $this->type = $type;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function autoincrement(bool $flag = true)
    {
        $this->autoIncrement = $flag;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function length(?int $length)
    {
        $this->length = $length;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function comment(?string $comment)
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
    public function precision(?int $precision, ?int $scale = 0)
    {
        $this->precision = $precision;
        $this->scale     = $scale;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function nillable(bool $flag = true)
    {
        $this->nillable = $flag;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function unsigned(bool $flag = true)
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
    public function fixed(bool $flag = true)
    {
        $this->fixed = $flag;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function name(string $name)
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
    public function build(): ColumnInterface
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
    public function indexes(): array
    {
        return $this->indexes;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }
}
