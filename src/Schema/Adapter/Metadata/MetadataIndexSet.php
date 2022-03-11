<?php

namespace Bdf\Prime\Schema\Adapter\Metadata;

use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Schema\Adapter\NamedIndex;
use Bdf\Prime\Schema\Bag\Index;
use Bdf\Prime\Schema\IndexInterface;
use Bdf\Prime\Schema\IndexSetInterface;

/**
 * Adapt Metadata to IndexSet
 */
final class MetadataIndexSet implements IndexSetInterface
{
    /**
     * @var Metadata
     */
    private $metadata;


    /**
     * MetadataIndexSet constructor.
     *
     * @param Metadata $metadata
     */
    public function __construct(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function primary(): MetadataPrimaryKeyIndex
    {
        return new MetadataPrimaryKeyIndex($this->metadata->primary);
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        $indexes =  $this->extractIndexes($this->metadata->indexes);

        $primary = $this->primary();
        $indexes[$primary->name()] = $primary;

        return array_change_key_case($indexes, CASE_LOWER);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name): IndexInterface
    {
        return $this->all()[strtolower($name)];
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $name): bool
    {
        return isset($this->all()[strtolower($name)]);
    }

    /**
     * @param array $indexes
     *
     * @return array<string, NamedIndex>
     */
    private function extractIndexes(array $indexes)
    {
        $output = [];

        foreach ($indexes as $name => $meta) {
            $fields = $meta['fields'];
            unset($meta['fields']);

            $type = IndexInterface::TYPE_SIMPLE;

            if (!empty($meta['unique'])) {
                $type = IndexInterface::TYPE_UNIQUE;
                unset($meta['unique']);
            }

            $index = new NamedIndex(
                new Index($fields, $type, $name, $meta),
                $this->metadata->table
            );

            $output[$index->name()] = $index;
        }

        return $output;
    }
}
