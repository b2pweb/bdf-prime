<?php

namespace Php84\Fixtures;

use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Relations\Builder\RelationBuilder;
use Override;

class EntityWithGetterPropertyMapper extends Mapper
{
    #[Override]
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table' => 'entity_with_getter_property',
        ];
    }

    #[Override]
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->autoincrement()
            ->string('email')->unique()
            ->string('password')
            ->string('normalizedEmail')->alias('normalized_email')->unique()->virtual()
        ;
    }

    #[Override]
    public function buildRelations(RelationBuilder $builder): void
    {
        $builder->on('characters')
            ->hasMany(EntityWithPropertyHook::class.'::ownerId')
        ;
    }
}
