<?php

namespace Php84\Fixtures;

use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Relations\Builder\RelationBuilder;
use Override;

class EntityWithPropertyHookMapper extends Mapper
{
    #[Override]
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table' => 'entity_with_property_hook',
        ];
    }

    #[Override]
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->autoincrement()
            ->integer('ownerId')->alias('owner_id')
            ->string('name')
            ->json('stats')->jsonObjectAsArray()
            ->integer('strength')
            ->integer('intelligence')
            ->integer('agility')
        ;
    }

    #[Override]
    public function buildRelations(RelationBuilder $builder): void
    {
        $builder->on('owner')
            ->belongsTo(EntityWithGetterProperty::class, 'ownerId')
        ;
    }
}
