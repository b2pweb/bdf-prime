<?php

namespace Bdf\Prime\Schema\Adapter\Metadata;

use Bdf\Prime\Schema\Adapter\AbstractIndex;
use Bdf\Prime\Schema\Adapter\NamedIndex;

/**
 * Adapt primary key metadata for index
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
    public function name()
    {
        return NamedIndex::FOR_PRIMARY;
    }

    /**
     * {@inheritdoc}
     */
    public function type()
    {
        return self::TYPE_PRIMARY;
    }

    /**
     * {@inheritdoc}
     */
    public function fields()
    {
        return $this->metadata['fields'];
    }

    /**
     * {@inheritdoc}
     */
    public function options()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function fieldOptions($field)
    {
        return [];
    }
}
