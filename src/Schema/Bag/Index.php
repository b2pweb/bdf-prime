<?php

namespace Bdf\Prime\Schema\Bag;

use Bdf\Prime\Schema\Adapter\AbstractIndex;
use Bdf\Prime\Schema\IndexInterface;

/**
 * Index using simple array of fields
 *
 * @psalm-immutable
 */
final class Index extends AbstractIndex
{
    /**
     * @var array<string, array>
     */
    private $fields;

    /**
     * @var IndexInterface::TYPE_*
     */
    private $type;

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var array
     */
    private $options;


    /**
     * ArrayIndex constructor.
     *
     * @param array<string,array> $fields The fields as key, and option as value
     * @param IndexInterface::TYPE_* $type
     * @param string|null $name
     * @param array $options
     */
    public function __construct(array $fields, int $type = self::TYPE_SIMPLE, ?string $name = null, array $options = [])
    {
        $this->fields = $fields;
        $this->type = $type;
        $this->name = $name;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function name(): ?string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function type(): int
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function fields(): array
    {
        return array_keys($this->fields);
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
    public function fieldOptions(string $field): array
    {
        return $this->fields[$field] ?? [];
    }
}
