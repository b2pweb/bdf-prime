<?php

use Bdf\Prime\Entity\Extensions\ArrayInjector;
use Bdf\Prime\Mapper\SingleTableInheritanceMapper;
use Bdf\Prime\Mapper\Mapper;


class ParentEntity
{
    use ArrayInjector;

    public $id;
    public $name;
    public $targetId;
    public $target;
    public $typeId;
    public $dateInsert;


    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class ParentEntityMapper extends SingleTableInheritanceMapper
{
    protected $discriminatorColumn = 'typeId';

    protected $discriminatorMap = [
        'child1' => 'ChildEntity1Mapper',
        'child2' => 'ChildEntity2Mapper',
    ];


    /**
     * {@inheritdoc}
     */
    public function schema()
    {
        return [
            'connection' => 'test',
            'database'   => 'test',
            'table'      => 'parent_'
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildFields($builder)
    {
        $builder
            ->integer('id')
                ->autoincrement()
                
            ->string('name')
                
            ->string('typeId')
                ->alias('type_id')
                
            ->bigint('targetId', 0)
                ->alias('target_id')
                
            ->datetime('dateInsert')->alias('date_insert')->nillable()
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildRelations($builder)
    {
        $builder->on('target')
            ->inherit('targetId');
    }
}

class ChildEntity1 extends ParentEntity
{
    public $typeId = 'child1';
}

class ChildEntity1Mapper extends ParentEntityMapper
{
    /**
     * {@inheritdoc}
     */
    public function buildRelations($builder)
    {
        parent::buildRelations($builder);

        $builder->on('target')
            ->belongsTo('ChildRelation1', 'targetId');
    }
}

class ChildEntity2 extends ParentEntity
{
    public $typeId = 'child2';
}

class ChildEntity2Mapper extends ParentEntityMapper
{
    /**
     * {@inheritdoc}
     */
    public function buildRelations($builder)
    {
        parent::buildRelations($builder);

        $builder->on('target')
            ->belongsTo('ChildRelation2::id', 'targetId');
    }
}

class ChildRelation1
{
    use ArrayInjector;

    public $id;
    public $description;
    public $relation2;

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}


class ChildRelation1Mapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema()
    {
        return [
            'connection' => 'test',
            'database'   => 'test',
            'table'      => 'childrelation1_'
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildFields($builder)
    {
        $builder
            ->integer('id')
                ->autoincrement()
                
            ->string('description')

            ->embedded('relation2', 'ChildRelation2', function($builder) {
                $builder->bigint('id')->nillable()->alias('relation_id');
            })
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRelations($builder)
    {
        $builder->on('relation2')
            ->belongsTo('ChildRelation2::id', 'relation2.id');
    }
}

class ChildRelation2
{
    use ArrayInjector;

    public $id;
    public $name;

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}


class ChildRelation2Mapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema()
    {
        return [
            'connection' => 'test',
            'database'   => 'test',
            'table'      => 'childrelation2_'
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildFields($builder)
    {
        $builder
            ->integer('id')
                ->autoincrement()
                
            ->string('name')
        ;
    }
}
