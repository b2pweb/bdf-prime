<?php

namespace Bdf\Prime;

use Bdf\Prime\Mapper\Mapper;

class TestFiltersEntityMapper extends Mapper
{
    /**
     * @var array
     */
    public $filters = [];

    /**
     * {@inheritdoc}
     */
    public function schema()
    {
        return [
            'connection' => 'test',
            'database' => 'test',
            'table' => 'test_filters',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields($builder)
    {
        $builder
            ->integer('id')->primary()
            ->string('name', 90)
            ->searchableArray('array')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function filters()
    {
        return $this->filters;
    }
}

class TestFiltersEntity
{
    public $id;

    public $name;

    public $array = [];
}