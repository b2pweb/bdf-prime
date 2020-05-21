<?php

namespace Bdf\Prime\Relations;


use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Mapper;

class EntityWithCustomRelation extends Model
{
    public $key1;
    public $key2;
    public $value;

    /**
     * @var DistantEntityForCustomRelation
     */
    public $distant;

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);

        $this->distant = new DistantEntityForCustomRelation();
    }
}

class EntityWithCustomRelationMapper extends Mapper
{
    /**
     * @inheritDoc
     */
    public function schema()
    {
        return [
            'connection' => 'test',
            'table' => 'EntityWithCustomRelation'
        ];
    }

    public function buildFields($builder)
    {
        $builder
            ->string('key1')->primary()
            ->string('key2')->primary()
            ->string('value')->nillable()
        ;
    }

    public function buildRelations($builder)
    {
        $builder->on('distant')
            ->custom(MyCustomRelation::class)
            ->entity(DistantEntityForCustomRelation::class)
            ->option('keys', [
                'key1' => 'key1',
                'key2' => 'key2',
            ])
        ;
    }
}

class DistantEntityForCustomRelation extends Model
{
    public $key1;
    public $key2;
    public $value;

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class DistantEntityForCustomRelationMapper extends Mapper
{
    /**
     * @inheritDoc
     */
    public function schema()
    {
        return [
            'connection' => 'test',
            'table' => 'DistantEntityForCustomRelation'
        ];
    }

    public function buildFields($builder)
    {
        $builder
            ->string('key1')->alias('dist_k1')->primary()
            ->string('key2')->alias('dist_k2')->primary()
            ->string('value')->alias('dist_val')->nillable()
        ;
    }
}

class EntityForeignInOwner extends Model
{
    public $id;
    public $fk1;
    public $fk2;

    /**
     * @var EntityForeignIn[]
     */
    public $relation;

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }

}

class EntityForeignInOwnerMapper extends Mapper
{
    /**
     * @inheritDoc
     */
    public function schema()
    {
        return [
            'connection' => 'test',
            'table' => 'EntityForeignInOwner'
        ];
    }

    public function buildFields($builder)
    {
        $builder
            ->integer('id')->autoincrement()
            ->string('fk1')
            ->string('fk2')
        ;
    }

    public function buildRelations($builder)
    {
        $builder->on('relation')
            ->custom(ForeignInRelation::class)
            ->entity(EntityForeignIn::class)
            ->option('localKeys', ['fk1', 'fk2'])
        ;
    }
}

class EntityForeignIn extends Model
{
    public $id;
    public $value;


    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class EntityForeignInMapper extends Mapper
{
    /**
     * @inheritDoc
     */
    public function schema()
    {
        return [
            'connection' => 'test',
            'table' => 'EntityForeignIn'
        ];
    }

    public function buildFields($builder)
    {
        $builder
            ->integer('id')->autoincrement()
            ->string('value')
        ;
    }
}