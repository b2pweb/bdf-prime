<?php

namespace Php80\Mapper\_files;

use Bdf\Prime\Mapper\Attribute\DisableSchemaManager;
use Bdf\Prime\Mapper\Attribute\DisableWrite;
use Bdf\Prime\Mapper\Attribute\UseQuoteIdentifier;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Mapper;

#[UseQuoteIdentifier]
class WithQuoteIdentifierEntityMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'readonly_entity',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->autoincrement()
            ->string('name')
        ;
    }
}
