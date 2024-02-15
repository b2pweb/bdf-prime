<?php

namespace Php80\Mapper\_files;

use Bdf\Prime\Mapper\Attribute\RepositoryMethod;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Query\Expression\Like;
use Bdf\Prime\Repository\EntityRepository;

class EntityWithConstraintMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'entity_with_constraint',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->autoincrement()
            ->string('name')
            ->boolean('active')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function customConstraints(): array
    {
        return [
            'active' => true,
            'id >' => 5,
        ];
    }

    #[RepositoryMethod(jit: true)]
    public function search(EntityRepository $repository, string $name): array
    {
        return $repository
            ->where('name', $name)
            ->limit(15)
            ->all()
        ;
    }
}
