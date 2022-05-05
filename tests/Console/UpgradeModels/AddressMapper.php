<?php

namespace Console\UpgradeModels;

use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Mapper;

class AddressMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'table' => 'address',
            'connection' => 'test',
        ];
    }

    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->autoincrement()
            ->string('street')
            ->integer('number')
            ->string('city')
            ->string('zipCode')
            ->string('country')
        ;
    }
}
