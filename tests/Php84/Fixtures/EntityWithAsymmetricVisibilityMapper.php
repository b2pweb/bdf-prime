<?php

namespace Php84\Fixtures;

use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Mapper;
use Override;

class EntityWithAsymmetricVisibilityMapper extends Mapper
{
    #[Override]
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table' => 'entity_with_asymmetric_visibility',
        ];
    }

    #[Override]
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->autoincrement()
            ->string('name')
            ->string('secret')->nillable()
        ;
    }
}
