<?php

namespace Bdf\Prime\Schema\Adapter\Metadata;

use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Platform\PlatformTypeInterface;
use Bdf\Prime\Platform\PlatformTypesInterface;
use Bdf\Prime\Schema\ColumnInterface;

/**
 * Column representation using Metadata field
 */
final class MetadataColumn implements ColumnInterface
{
    /**
     * @var array
     */
    private $metadata;

    /**
     * @var PlatformTypesInterface
     */
    private $types;


    /**
     * MetadataColumn constructor.
     *
     * @param array $metadata
     * @param PlatformTypesInterface $types
     */
    public function __construct(array $metadata, PlatformTypesInterface $types)
    {
        $this->metadata = $metadata;
        $this->types = $types;
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return $this->metadata['field'];
    }

    /**
     * {@inheritdoc}
     */
    public function type(): PlatformTypeInterface
    {
        return $this->types->native($this->metadata['type']);
    }

    /**
     * {@inheritdoc}
     */
    public function defaultValue()
    {
        return $this->type()->toDatabase($this->metadata['default']);
    }

    /**
     * {@inheritdoc}
     */
    public function length(): ?int
    {
        return $this->metadata['length'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function autoIncrement(): bool
    {
        return !empty($this->metadata['primary']) && $this->metadata['primary'] === Metadata::PK_AUTOINCREMENT;
    }

    /**
     * {@inheritdoc}
     */
    public function unsigned(): bool
    {
        return $this->metadata['unsigned'] ?? false;
    }

    /**
     * {@inheritdoc}
     */
    public function fixed(): bool
    {
        return $this->metadata['fixed'] ?? false;
    }

    /**
     * {@inheritdoc}
     */
    public function nillable(): bool
    {
        return $this->metadata['nillable'] ?? false;
    }

    /**
     * {@inheritdoc}
     */
    public function comment(): ?string
    {
        return $this->metadata['comment'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function precision(): ?int
    {
        return $this->metadata['precision'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function scale(): ?int
    {
        return $this->metadata['scale'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function options(): array
    {
        return $this->metadata['customSchemaOptions'] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function option(string $name)
    {
        return $this->metadata['customSchemaOptions'][$name];
    }

    /**
     * Get the metadata array
     *
     * @return array
     */
    public function toMetadata(): array
    {
        return $this->metadata;
    }
}
