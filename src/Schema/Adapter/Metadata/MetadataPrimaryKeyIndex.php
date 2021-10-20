<?php

namespace Bdf\Prime\Schema\Adapter\Metadata;

use Bdf\Prime\Schema\Adapter\AbstractIndex;
use Bdf\Prime\Schema\Adapter\NamedIndex;

/**
 * Adapt primary key metadata for index
 *
 * @psalm-immutable
 */
final class MetadataPrimaryKeyIndex extends AbstractIndex
{
    /**
     * @var array
     */
    private $metadata;


    /**
     * MetadataPrimaryKeyIndex constructor.
     *
     * @param array $metadata
     */
    public function __construct(array $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return NamedIndex::FOR_PRIMARY;
    }

    /**
     * {@inheritdoc}
     */
    public function type(): int
    {
        return self::TYPE_PRIMARY;
    }

    /**
     * {@inheritdoc}
     */
    public function fields(): array
    {
        return $this->metadata['fields'];
    }

    /**
     * {@inheritdoc}
     */
    public function options(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function fieldOptions(string $field): array
    {
        return [];
    }
}
