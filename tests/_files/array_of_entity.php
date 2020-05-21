<?php

namespace Bdf\Prime;

use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Mapper;

class EntityArrayOf extends Model
{
    public $id;
    public $floats = [];
    public $booleans = [];
    public $dates = [];

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class EntityArrayOfMapper extends Mapper
{
    /**
     * @inheritDoc
     */
    public function schema()
    {
        return [
            'connection' => 'test',
            'table' => 'array_of_entity'
        ];
    }

    /**
     * @inheritDoc
     */
    public function buildFields($builder)
    {
        $builder
            ->integer('id')->autoincrement()
            ->arrayOfDouble('floats')
            ->arrayOfDateTime('dates')
            ->arrayOf('booleans', 'boolean')
        ;
    }
}
