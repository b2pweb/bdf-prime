<?php

namespace Bdf\Prime;

use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Builder\IndexBuilder;
use Bdf\Prime\Mapper\Mapper;

/**
 * Class EntityWithIndex
 */
class EntityWithIndex extends Model
{
    public $id;
    public $guid;
    public $firstName;
    public $lastName;
    public $address;
    public $zipCode;
}

class EntityWithIndexMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema()
    {
        return [
            'connection'   => 'test',
            'database'     => 'test',
            'table'        => 'test_entity_with_indexes_',
            'tableOptions' => ['foo' => 'bar'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields($builder)
    {
        $builder
            ->integer('id')->alias('id_')->autoincrement()
            ->string('guid')->alias('guid_')->unique()
            ->string('firstName')->alias('first_name')->unique('name')
            ->string('lastName')->alias('last_name')->unique('name')
            ->string('address')->alias('address_')
            ->string('zipCode')->alias('zip_code')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function indexes()
    {
        return [
            ['address', 'zipCode']
        ];
    }
}

class EntityWithIndexV15 extends Model
{
    public $id;
    public $guid;
    public $firstName;
    public $lastName;
    public $address;
    public $zipCode;
}

class EntityWithIndexV15Mapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema()
    {
        return [
            'connection'   => 'test',
            'database'     => 'test',
            'table'        => 'test_entity_with_indexes_v15_',
            'tableOptions' => ['foo' => 'bar'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields($builder)
    {
        $builder
            ->integer('id')->alias('id_')->autoincrement()
            ->string('guid')->alias('guid_')
            ->string('firstName')->alias('first_name')
            ->string('lastName')->alias('last_name')
            ->string('address')->alias('address_')
            ->string('zipCode')->alias('zip_code')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildIndexes(IndexBuilder $builder)
    {
        $builder
            ->add()->on('guid')->unique()
            ->add('name')->on(['firstName', 'lastName'])->unique()
            ->add()->on(['address' => ['length' => 24], 'zipCode'])
        ;
    }
}

class PartialIndexEntity extends Model
{
    public $id;
    public $value;

    public function __construct(array $data = [])
    {
        $this->import($data);
    }
}

class PartialIndexEntityMapper extends Mapper
{
    public function schema()
    {
        return [
            'connection'   => 'test',
            'database'     => 'test',
            'table'        => 'partial_index_entity_',
        ];
    }

    public function buildFields($builder)
    {
        $builder
            ->integer('id')->autoincrement()
            ->integer('value')
        ;
    }

    public function buildIndexes(IndexBuilder $builder)
    {
        $builder
            ->add()->on('value')->option('where', 'value < 42')->unique()
        ;
    }
}
