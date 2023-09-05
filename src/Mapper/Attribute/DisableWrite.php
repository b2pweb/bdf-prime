<?php

namespace Bdf\Prime\Mapper\Attribute;

use Attribute;
use Bdf\Prime\Mapper\Mapper;

/**
 * Mark the mapper as read-only
 *
 * @see Mapper::setReadOnly()
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class DisableWrite implements MapperConfigurationInterface
{
    private bool $readOnly;

    /**
     * @param bool $readOnly
     */
    public function __construct(bool $readOnly = true)
    {
        $this->readOnly = $readOnly;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(Mapper $mapper): void
    {
        $mapper->setReadOnly($this->readOnly);
    }
}
