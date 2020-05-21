<?php

namespace Bdf\Prime;

use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Mapper;

class CrossConnectionSequenceEntity extends Model
{
    public $id;
    public $name;

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class CrossConnectionSequenceEntityMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema()
    {
        return [
            'connection' => 'test',
            'database'   => 'test',
            'table'      => 'test_cross_connection_',
        ];
    }

    public function sequence()
    {
        return [
            'connection'   => 'sequence',
            'table'        => 'test_cross_connection_seq_',
            'tableOptions' => [],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields($builder)
    {
        $builder
            ->integer('id')->sequence()
            ->string('name')
        ;
    }
}
