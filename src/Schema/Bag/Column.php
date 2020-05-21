<?php

namespace Bdf\Prime\Schema\Bag;

use Bdf\Prime\Platform\PlatformTypeInterface;
use Bdf\Prime\Schema\ColumnInterface;

/**
 * Column object representation
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
     * @var int
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
     * @var array
     */
    private $options;


    /**
     * Column constructor.
     *
     * @param string $name
     * @param PlatformTypeInterface $type
     * @param mixed $defaultValue
     * @param int $length
     * @param bool $autoIncrement
     * @param bool $unsigned
     * @param bool $fixed
     * @param bool $nillable
     * @param string $comment
     * @param int $precision
     * @param int $scale
     * @param array $options
     */
    public function __construct($name, PlatformTypeInterface $type, $defaultValue = null, $length = null, $autoIncrement = false, $unsigned = false, $fixed = false, $nillable = false, $comment = null, $precision = 10, $scale = 0, array $options = [])
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
    public function name()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function type()
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
    public function length()
    {
        return $this->length;
    }

    /**
     * {@inheritdoc}
     */
    public function autoIncrement()
    {
        return $this->autoIncrement;
    }

    /**
     * {@inheritdoc}
     */
    public function unsigned()
    {
        return $this->unsigned;
    }

    /**
     * {@inheritdoc}
     */
    public function fixed()
    {
        return $this->fixed;
    }

    /**
     * {@inheritdoc}
     */
    public function nillable()
    {
        return $this->nillable;
    }

    /**
     * {@inheritdoc}
     */
    public function comment()
    {
        return $this->comment;
    }

    /**
     * {@inheritdoc}
     */
    public function precision()
    {
        return $this->precision;
    }

    /**
     * {@inheritdoc}
     */
    public function scale()
    {
        return $this->scale;
    }

    /**
     * {@inheritdoc}
     */
    public function options()
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function option($name)
    {
        return $this->options[$name];
    }
}
