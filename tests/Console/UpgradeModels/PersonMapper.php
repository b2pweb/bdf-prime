<?php

namespace Console\UpgradeModels;

use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Mapper;

class PersonMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'table' => 'person',
            'connection' => 'test',
        ];
    }

    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->autoincrement()
            ->string('firstName')
            ->string('lastName')
            ->embedded('address', Address::class, function (FieldBuilder $builder) {
                $builder->integer('id')->alias('address_id');
            })
        ;
    }
}
