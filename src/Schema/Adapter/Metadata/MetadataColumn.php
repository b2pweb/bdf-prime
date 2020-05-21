<?php

namespace Bdf\Prime\Schema\Adapter\Metadata;

use Bdf\Prime\Mapper\Metadata;
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
    public function name()
    {
        return $this->metadata['field'];
    }

    /**
     * {@inheritdoc}
     */
    public function type()
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
    public function length()
    {
        return isset($this->metadata['length']) ? $this->metadata['length'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function autoIncrement()
    {
        return !empty($this->metadata['primary']) && $this->metadata['primary'] === Metadata::PK_AUTOINCREMENT;
    }

    /**
     * {@inheritdoc}
     */
    public function unsigned()
    {
        return isset($this->metadata['unsigned']) ? $this->metadata['unsigned'] : false;
    }

    /**
     * {@inheritdoc}
     */
    public function fixed()
    {
        return isset($this->metadata['fixed']) ? $this->metadata['fixed'] : false;
    }

    /**
     * {@inheritdoc}
     */
    public function nillable()
    {
        return isset($this->metadata['nillable']) ? $this->metadata['nillable'] : false;
    }

    /**
     * {@inheritdoc}
     */
    public function comment()
    {
        return isset($this->metadata['comment']) ? $this->metadata['comment'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function precision()
    {
        return isset($this->metadata['precision']) ? $this->metadata['precision'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function scale()
    {
        return isset($this->metadata['scale']) ? $this->metadata['scale'] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function options()
    {
        return isset($this->metadata['customSchemaOptions']) ? $this->metadata['customSchemaOptions'] : [];
    }

    /**
     * {@inheritdoc}
     */
    public function option($name)
    {
        return $this->metadata['customSchemaOptions'][$name];
    }

    /**
     * Get the metadata array
     *
     * @return array
     */
    public function toMetadata()
    {
        return $this->metadata;
    }
}
