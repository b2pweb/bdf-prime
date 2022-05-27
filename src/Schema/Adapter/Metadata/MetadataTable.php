<?php

namespace Bdf\Prime\Schema\Adapter\Metadata;

use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Platform\PlatformTypesInterface;
use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Schema\Constraint\ConstraintSet;
use Bdf\Prime\Schema\ConstraintSetInterface;
use Bdf\Prime\Schema\IndexInterface;
use Bdf\Prime\Schema\IndexSetInterface;
use Bdf\Prime\Schema\TableInterface;

/**
 * Implements TableInterface using Metadata
 */
final class MetadataTable implements TableInterface
{
    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * @var PlatformTypesInterface
     */
    private $types;


    /**
     * MetadataTable constructor.
     *
     * @param Metadata $metadata
     * @param PlatformTypesInterface $types
     */
    public function __construct(Metadata $metadata, PlatformTypesInterface $types)
    {
        $this->metadata = $metadata;
        $this->types = $types;
    }

    /**
     * {@inheritdoc}
     */
    public function column(string $name): ColumnInterface
    {
        return new MetadataColumn($this->metadata->fields[$name], $this->types);
    }

    /**
     * {@inheritdoc}
     */
    public function columns(): array
    {
        return array_map(fn ($meta) => $this->column($meta['field']), $this->metadata->fields);
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return $this->metadata->table;
    }

    /**
     * Get the primary key index of the table
     *
     * @return IndexInterface|null The index, or null if the table has no primary key
     */
    public function primary(): ?IndexInterface
    {
        return $this->indexes()->primary();
    }

    /**
     * {@inheritdoc}
     */
    public function indexes(): IndexSetInterface
    {
        return new MetadataIndexSet($this->metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function constraints(): ConstraintSetInterface
    {
        return ConstraintSet::blank();
    }

    /**
     * {@inheritdoc}
     */
    public function options(): array
    {
        return $this->metadata->tableOptions();
    }

    /**
     * {@inheritdoc}
     */
    public function option(string $name)
    {
        return $this->metadata->tableOptions()[$name];
    }
}
