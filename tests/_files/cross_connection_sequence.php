<?php

namespace Bdf\Prime;

use Bdf\Prime\Mapper\Builder\FieldBuilder;
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
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'database'   => 'test',
            'table'      => 'test_cross_connection_',
        ];
    }

    public function sequence(): array
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
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->sequence()
            ->string('name')
        ;
    }
}
