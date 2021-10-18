<?php

namespace Bdf\Prime;

use Bdf\Prime\Relations\Builder\RelationBuilder;
use Bdf\Prime\Entity\InitializableInterface;
use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Mapper;

class DoubleJoinEntityMaster extends Model implements InitializableInterface
{
    /**
     * @var DoubleJoinEntitySub
     */
    public $sub;

    /**
     * @var DoubleJoinEntitySub2
     */
    public $sub2;

    public function __construct()
    {
        $this->initialize();
    }

    /**
     * @inheritDoc
     */
    public function initialize()
    {
        $this->sub = new DoubleJoinEntitySub();
        $this->sub2 = new DoubleJoinEntitySub2();
    }
}

class DoubleJoinEntitySub extends Model
{
    public $id;
    public $name;
}

class DoubleJoinEntitySub2 extends Model implements InitializableInterface
{
    public $id;

    /**
     * @var DoubleJoinEntitySub
     */
    public $sub;

    public function __construct()
    {
        $this->initialize();
    }

    /**
     * @inheritDoc
     */
    public function initialize()
    {
        $this->sub = new DoubleJoinEntitySub();
    }
}

class DoubleJoinEntityMasterMapper extends Mapper
{
    /**
     * @inheritDoc
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table' => 'double_join_entity_master'
        ];
    }

    /**
     * @inheritDoc
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->embedded('sub', DoubleJoinEntitySub::class, function (FieldBuilder $builder) {
                $builder->add('id')->alias('sub_id');
            })
            ->embedded('sub2', DoubleJoinEntitySub2::class, function (FieldBuilder $builder) {
                $builder->add('id')->alias('sub2_id');
            })
        ;
    }

    /**
     * @inheritDoc
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        $builder
            ->on('sub')->belongsTo(DoubleJoinEntitySub::class, 'sub.id')
            ->on('sub2')->belongsTo(DoubleJoinEntitySub2::class, 'sub2.id')
        ;
    }
}

class DoubleJoinEntitySubMapper extends Mapper
{

    /**
     * @inheritDoc
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table' => 'double_join_entity_sub'
        ];
    }

    /**
     * @inheritDoc
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->autoincrement()
            ->string('name')
        ;
    }
}


class DoubleJoinEntitySub2Mapper extends Mapper
{

    /**
     * @inheritDoc
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table' => 'double_join_entity_sub2'
        ];
    }

    /**
     * @inheritDoc
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->autoincrement()
            ->embedded('sub', DoubleJoinEntitySub::class, function (FieldBuilder $builder) {
                $builder->add('id')->alias('sub_id');
            })
        ;
    }

    /**
     * @inheritDoc
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        $builder
            ->on('sub')->belongsTo(DoubleJoinEntitySub::class, 'sub.id');
    }
}