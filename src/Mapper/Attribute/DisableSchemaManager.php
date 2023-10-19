<?php

namespace Bdf\Prime\Mapper\Attribute;

use Attribute;
use Bdf\Prime\Mapper\Mapper;

/**
 * Disable the schema manager for the mapper
 *
 * @see Mapper::disableSchemaManager()
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class DisableSchemaManager implements MapperConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(Mapper $mapper): void
    {
        $mapper->disableSchemaManager();
    }
}
