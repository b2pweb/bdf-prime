<?php

namespace Bdf\Prime;

use Bdf\Prime\Mapper\Builder\FieldBuilder;
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
    public function schema(): array
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
    public function buildFields(FieldBuilder $builder): void
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
    public function filters(): array
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