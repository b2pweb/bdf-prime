<?php

namespace Php80\Mapper\_files;

use Bdf\Prime\Mapper\Attribute\CriteriaClass;
use Bdf\Prime\Mapper\Attribute\RepositoryClass;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Mapper;

#[RepositoryClass(CustomRepository::class), CriteriaClass(CustomCriteria::class)]
class CustomRepositoryEntityMapper extends Mapper
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
