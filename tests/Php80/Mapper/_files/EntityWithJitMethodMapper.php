<?php

namespace Php80\Mapper\_files;

use Bdf\Prime\Collection\EntityCollection;
use Bdf\Prime\Mapper\Attribute\RepositoryMethod;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Query\Custom\BulkInsert\BulkInsertQuery;
use Bdf\Prime\Relations\Builder\RelationBuilder;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Prime\TestEntity;

class EntityWithJitMethodMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'entity_with_jit_method',
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
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        $builder->on('rel')
            ->belongsTo(TestEntity::class, 'id')
        ;
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

    #[RepositoryMethod(jit: true)]
    public function ambiguous(EntityRepository $repository, int $id1, int $id2): array
    {
        return $repository
            ->where('id', '>=', $id1)
            ->where('id', '<=', $id2)
            ->all()
        ;
    }

    #[RepositoryMethod(jit: true)]
    public function withConstant(EntityRepository $repository, int $id): array
    {
        return $repository
            ->where('name', 'foo')
            ->where('id', '>=', $id)
            ->limit(15)
            ->inRows('id')
        ;
    }

    #[RepositoryMethod(jit: true)]
    public function withMethodCall(EntityRepository $repository, string $name): array
    {
        if (!$this->hasAccess()) {
            return [];
        }

        return $repository
            ->where('name', $name)
            ->limit(15)
            ->all()
        ;
    }

    #[RepositoryMethod(jit: true)]
    public function notConstantQuery(EntityRepository $repository, string $field, $value): ?EntityWithJitMethod
    {
        return $repository->where($field, $value)->first();
    }

    #[RepositoryMethod(jit: true)]
    public function useKeyValueQuery(EntityRepository $repository, string $name): ?EntityWithJitMethod
    {
        return $repository->keyValue()->where('name', $name)->first();
    }

    #[RepositoryMethod(jit: true)]
    public function missingRepositoryParameter()
    {
        return 'ok';
    }

    #[RepositoryMethod(jit: true)]
    public function missingRepositoryParameter2()
    {
        return EntityWithJitMethod::where('id', 0)->first();
    }

    #[RepositoryMethod(jit: true)]
    public function notSelectQuery(EntityRepository $repository)
    {
        $repository->queries()->make(BulkInsertQuery::class)->values(['name' => 'foo'])->execute();
    }

    #[RepositoryMethod(jit: true)]
    public function changingArgumentMapping(EntityRepository $repository, int $id1, int $id2): array
    {
        return $repository
            ->where('id', '>=', min($id1, $id2))
            ->where('id', '<=', max($id2, $id2))
            ->all()
        ;
    }

    #[RepositoryMethod(jit: true)]
    public function constantNotSoConstant(EntityRepository $repository): array
    {
        return $repository->where('id', rand(0, 100))->all();
    }

    #[RepositoryMethod(jit: true)]
    public function withCustomMetadata(EntityRepository $repository, string $name): EntityCollection
    {
        return $repository->where('name', $name)
            ->wrapAs(EntityCollection::class)
            ->useCache(3600)
            ->all()
        ;
    }

    #[RepositoryMethod(jit: true)]
    public function withExtensionCalls(EntityRepository $repository, int $id1, int $id2): array
    {
        return $repository
            ->where('id', '>=', $id1)
            ->where('id', '<=', $id2)
            ->by('name', true)
            ->with('rel')
            ->all()
        ;
    }

    #[RepositoryMethod(jit: true)]
    public function withNotConstantExtensionCall(EntityRepository $repository): array
    {
        static $bestCode = 0;

        return $repository
            ->by((++$bestCode % 2) === 0 ? 'id': 'name')
            ->all()
        ;
    }

    #[RepositoryMethod(jit: true)]
    public function metadataMappingChange(EntityRepository $repository, string $wrapper)
    {
        return $repository
            ->wrapAs($wrapper ?: 'array')
            ->all()
        ;
    }

    #[RepositoryMethod(jit: true)]
    public function constantMetadataChange(EntityRepository $repository, string $name)
    {
        return $repository
            ->where('name', $name)
            ->useCache(3600, md5($name))
            ->all()
        ;
    }

    #[RepositoryMethod(jit: true)]
    public function complexMethod(EntityRepository $repository, string $name): int
    {
        $hash = 17;

        foreach ($repository->where('name', $name)->inRows('id') as $id) {
            $hash ^= $id;
        }

        return $hash;
    }

    public function hasAccess(): bool
    {
        return true;
    }
}
