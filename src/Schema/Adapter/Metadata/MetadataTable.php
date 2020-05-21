<?php

namespace Bdf\Prime\Schema\Adapter\Metadata;

use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Platform\PlatformTypesInterface;
use Bdf\Prime\Schema\Constraint\ConstraintSet;
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
    public function column($name)
    {
        return new MetadataColumn($this->metadata->fields[$name], $this->types);
    }

    /**
     * {@inheritdoc}
     */
    public function columns()
    {
        return array_map(function ($meta) {
            return $this->column($meta['field']);
        }, $this->metadata->fields);
    }

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return $this->metadata->table;
    }

    /**
     * {@inheritdoc}
     */
    public function primary()
    {
        return $this->indexes()->primary();
    }

    /**
     * {@inheritdoc}
     */
    public function indexes()
    {
        return new MetadataIndexSet($this->metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function constraints()
    {
        return ConstraintSet::blank();
    }

    /**
     * {@inheritdoc}
     */
    public function options()
    {
        return $this->metadata->tableOptions();
    }

    /**
     * {@inheritdoc}
     */
    public function option($name)
    {
        return $this->metadata->tableOptions()[$name];
    }
}
