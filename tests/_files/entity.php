<?php

namespace Bdf\Prime;

use Bdf\Prime\Entity\InitializableInterface;
use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Query\Custom\KeyValue\KeyValueQuery;
use Bdf\Prime\Repository\EntityRepository;

class TestEntity extends Model implements InitializableInterface
{
    public $id;
    public $name;
    public $foreign;
    public $dateInsert;
    public $parentId;
    public $parent;

    public function __construct(array $attributes = [])
    {
        $this->initialize();
        $this->import($attributes);
    }

    public function initialize()
    {
        $this->foreign = new TestEmbeddedEntity();
    }
}

class TestEntityMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema()
    {
        return [
            'connection' => 'test',
            'database' => 'test',
            'table' => 'test_',
//            'tableOptions' => [
//                'engine' => 'MyIsam'
//            ]
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

            ->embedded('foreign', 'Bdf\Prime\TestEmbeddedEntity', function($builder) {
                $builder->integer('id')->alias('foreign_key')->nillable();
            })

            ->datetime('dateInsert')->alias('date_insert')->nillable()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRelations($builder)
    {
        $builder->on('foreign')
            ->belongsTo('Bdf\Prime\TestEmbeddedEntity', 'foreign.id');
    }
    
    /**
     * {@inheritdoc}
     */
    public function filters()
    {
        return [
            'idLike' => function($query, $value) {
                $query->where(['id :like' => $value . '%']);
            },
            'nameLike' => function($query, $value) {
                $query->where(['name :like' => '%' . $value]);
            },
            'join' => function($query, $value) {
                $query->join('Bdf\Prime\TestEmbeddedEntity', 'id', 'foreign.id', 'f')
                    ->where(['f>name :like' => '%foreign']);
            }
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function scopes()
    {
        return [
            'testScope' => function($query) {
                return $query->limit(1)->execute(['test' => 1]);
            }
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function queries()
    {
        return [
            'testQuery' => function (EntityRepository $repository, $id) {
                return $repository->make(KeyValueQuery::class)->where('id', $id)->limit(1);
            }
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function customEvents($notifier)
    {
        $notifier->listen('afterLoad', function($entity) {
            if ($entity->name === 'event') {
                $entity->name = 'loaded';
            }
        });
    }
}