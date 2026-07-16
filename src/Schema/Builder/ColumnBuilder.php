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
    private string $name;

    private PlatformTypeInterface $type;

    /**
     * @var mixed
     */
    private $defaultValue;

    private ?int $length = null;

    private bool $autoIncrement = false;

    private bool $unsigned = false;

    private bool $fixed = false;

    private bool $nillable = false;

    private ?string $comment = null;

    private ?int $precision = null;

    private ?int $scale = null;

    /**
     * @var IndexInterface::TYPE_*[]
     */
    private array $indexes = [];

    private array $options = [];


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
    public function autoincrement(bool $flag = true): static
    {
        $this->autoIncrement = $flag;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function length(?int $length): static
    {
        $this->length = $length;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function comment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefault(mixed $value): static
    {
        $this->defaultValue = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function precision(?int $precision, ?int $scale = 0): static
    {
        $this->precision = $precision;
        $this->scale     = $scale;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function nillable(bool $flag = true): static
    {
        $this->nillable = $flag;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function unsigned(bool $flag = true): static
    {
        $this->unsigned = $flag;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function unique(bool|string $index = true): static
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
    public function fixed(bool $flag = true): static
    {
        $this->fixed = $flag;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function type(PlatformTypeInterface $type): static
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
