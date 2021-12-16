<?php

namespace Bdf\Prime\Schema\Bag;

use Bdf\Prime\Platform\PlatformTypeInterface;
use Bdf\Prime\Schema\ColumnInterface;

/**
 * Column object representation
 *
 * @psalm-immutable
 */
final class Column implements ColumnInterface
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
    private $autoIncrement;

    /**
     * @var bool
     */
    private $unsigned;

    /**
     * @var bool
     */
    private $fixed;

    /**
     * @var bool
     */
    private $nillable;

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
     * @var array<string, mixed>
     */
    private $options;


    /**
     * Column constructor.
     *
     * @param string $name
     * @param PlatformTypeInterface $type
     * @param mixed $defaultValue
     * @param int|null $length
     * @param bool $autoIncrement
     * @param bool $unsigned
     * @param bool $fixed
     * @param bool $nillable
     * @param string|null $comment
     * @param int|null $precision
     * @param int|null $scale
     * @param array<string, mixed> $options
     */
    public function __construct(string $name, PlatformTypeInterface $type, $defaultValue = null, ?int $length = null, bool $autoIncrement = false, bool $unsigned = false, bool $fixed = false, bool $nillable = false, ?string $comment = null, ?int $precision = 10, ?int $scale = 0, array $options = [])
    {
        $this->name = $name;
        $this->type = $type;
        $this->defaultValue = $defaultValue;
        $this->length = $length;
        $this->autoIncrement = $autoIncrement;
        $this->unsigned = $unsigned;
        $this->fixed = $fixed;
        $this->nillable = $nillable;
        $this->comment = $comment;
        $this->precision = $precision;
        $this->scale = $scale;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function type(): PlatformTypeInterface
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function defaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * {@inheritdoc}
     */
    public function length(): ?int
    {
        return $this->length;
    }

    /**
     * {@inheritdoc}
     */
    public function autoIncrement(): bool
    {
        return $this->autoIncrement;
    }

    /**
     * {@inheritdoc}
     */
    public function unsigned(): bool
    {
        return $this->unsigned;
    }

    /**
     * {@inheritdoc}
     */
    public function fixed(): bool
    {
        return $this->fixed;
    }

    /**
     * {@inheritdoc}
     */
    public function nillable(): bool
    {
        return $this->nillable;
    }

    /**
     * {@inheritdoc}
     */
    public function comment(): ?string
    {
        return $this->comment;
    }

    /**
     * {@inheritdoc}
     */
    public function precision(): ?int
    {
        return $this->precision;
    }

    /**
     * {@inheritdoc}
     */
    public function scale(): ?int
    {
        return $this->scale;
    }

    /**
     * {@inheritdoc}
     */
    public function options(): array
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function option(string $name)
    {
        return $this->options[$name];
    }
}
