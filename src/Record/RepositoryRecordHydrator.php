<?php

namespace Bdf\Prime\Record;

use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Repository\RepositoryInterface;
use InvalidArgumentException;
use ReflectionClass;

use function class_exists;
use function is_string;
use function sprintf;

/**
 * Record hydrator using the repository metadata
 */
final class RepositoryRecordHydrator implements RecordHydratorInterface
{
    private readonly string $entityClass;

    /**
     * @var array<class-string, EntityRecordInstantiator>
     * @psalm-var class-string-map<R, EntityRecordInstantiator<R>>
     */
    private array $cache = [];

    public function __construct(
        private readonly RepositoryInterface $repository,
    ) {
        $this->entityClass = $repository->entityClass();
    }

    /**
     * {@inheritdoc}
     */
    public function projection(string $recordClass): ?array
    {
        if ($recordClass === $this->entityClass  || !class_exists($recordClass)) {
            return null;
        }

        return $this->instantiator($recordClass)->instantiator->projection();
    }

    /**
     * {@inheritdoc}
     */
    public function prepare(string $recordClass, array $rows): array
    {
        if ($recordClass === $this->entityClass || !class_exists($recordClass)) {
            return $rows;
        }

        $loaded = $rows;

        foreach ($this->instantiator($recordClass)->relations as $relation) {
            $loaded = $relation->load($this->repository, $loaded);
        }

        return $loaded;
    }

    /**
     * {@inheritdoc}
     */
    public function finalize(string $recordClass, array $entities): array
    {
        return $entities;
    }

    /**
     * {@inheritdoc}
     *
     * @param class-string<T> $recordClass
     * @return T
     * @template T as object
     */
    public function instantiate(string $recordClass, array $data, PlatformInterface $platform): object
    {
        if ($recordClass === $this->entityClass  || !class_exists($recordClass)) {
            /** @var T */
            return $this->repository->mapper()->prepareFromRepository($data, $platform);
        }

        return $this->instantiator($recordClass)->instantiator->instantiate($data, $platform);
    }

    /**
     * @param class-string<R> $recordClass
     * @return EntityRecordInstantiator<R>
     * @template R as object
     */
    private function instantiator(string $recordClass): EntityRecordInstantiator
    {
        if (($instantiator = $this->cache[$recordClass] ?? null) !== null) {
            return $instantiator;
        }

        $constructorParameters = (new ReflectionClass($recordClass))->getConstructor()?->getParameters();

        if ($constructorParameters === null) {
            throw new InvalidArgumentException(sprintf('The record class %s must have a constructor', $recordClass));
        }

        $fields = [];
        $relations = [];
        $attributesMetadata = $this->repository->metadata()->attributes;

        foreach ($constructorParameters as $parameter) {
            foreach ($parameter->getAttributes(LoadRelation::class) as $loadRelationAttr) {
                $relAttr = $loadRelationAttr->newInstance();
                $relObj = $this->repository->relation($relAttr->relation);
                $relations[$parameter->name] = new RelationLoader(
                    relationName: $relAttr->relation,
                    target: $parameter->name,
                    foreignKeyProperty: $relObj->localKeyProperty(),
                    foreignKeyField: $attributesMetadata[$relObj->localKeyProperty()]['field'],
                );
                $fields[$parameter->name] = new Field(name: $parameter->name, projection: $relObj->localKeyProperty());
                continue 2;
            }

            $field = Field::fromReflectionParameter($parameter);

            // resolve the db type from the mapper, if possible
            if ($field->type === null && ($field->expression === null || is_string($field->expression))) {
                $field = $field->with(
                    type: $attributesMetadata[$field->expression ?? $field->name]['type'] ?? null,
                );
            }

            $field = $field->with(
                name: $attributesMetadata[$field->name]['field'] ?? $field->name,
                projection: $field->name
            );

            $fields[$field->name] = $field;
        }

        return $this->cache[$recordClass] = new EntityRecordInstantiator(
            new RecordInstantiator($recordClass, $fields),
            $relations
        );
    }
}

/**
 * @internal
 * @template R as object
 */
final class EntityRecordInstantiator
{
    public function __construct(
        /**
         * @var RecordInstantiator<R>
         */
        public readonly RecordInstantiator $instantiator,

        /**
         * @var array<string, RelationLoader>
         */
        public readonly array $relations,
    ) {
    }
}
