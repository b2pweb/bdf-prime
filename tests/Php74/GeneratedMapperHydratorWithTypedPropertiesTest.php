<?php

namespace Php74;

use Bdf\Prime\Bench\HydratorGeneration;

class GeneratedMapperHydratorWithTypedPropertiesTest extends MapperHydratorWithTypedPropertiesTest
{
    use HydratorGeneration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpGeneratedHydrators(SimpleEntity::class, EntityWithEmbedded::class);
    }
}
