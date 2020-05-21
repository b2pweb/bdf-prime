<?php

namespace Bdf\Prime\Schema\Bag;

use Bdf\Prime\Schema\Adapter\AbstractIndex;

/**
 * Index using simple array of fields
 */
final class Index extends AbstractIndex
{
    /**
     * @var array
     */
    private $fields;

    /**
     * @var int
     */
    private $type;

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $options;


    /**
     * ArrayIndex constructor.
     *
     * @param array $fields The fields as key, and option as value
     * @param int $type
     * @param string $name
     * @param array $options
     */
    public function __construct(array $fields, $type = self::TYPE_SIMPLE, $name = null, array $options = [])
    {
        $this->fields = $fields;
        $this->type = $type;
        $this->name = $name;
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
    public function fields()
    {
        return array_keys($this->fields);
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
    public function fieldOptions($field)
    {
        return $this->fields[$field] ?? [];
    }
}
