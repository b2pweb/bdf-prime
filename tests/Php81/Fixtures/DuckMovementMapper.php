<?php

namespace Php81\Fixtures;

use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Mapper;

class DuckMovementMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table' => 'duck_movement',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->autoincrement()
            ->integer('duckId')->alias('duck_id')
            ->integer('fromX')->alias('from_x')
            ->integer('fromY')->alias('from_y')
            ->integer('distance')
            ->unitEnum('direction', DirectionEnum::class)
            ->stringEnum('mean', MovementMeanEnum::class)
            ->intEnum('speed', SpeedEnum::class)
        ;
    }
}
