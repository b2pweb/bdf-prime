<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\Query\Contract\EntityJoinable;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Contract\WriteOperation;
use Bdf\Prime\Query\ReadCommandInterface;
use Bdf\Prime\Repository\RepositoryInterface;
use InvalidArgumentException;

use function array_intersect_key;

/**
 * OneOrMany
 *
 * @package Bdf\Prime\Relations
 *
 * @todo possibilité de désactiver les constraints globales
 *
 * @template L as object
 * @template R as object
 *
 * @extends Relation<L, R>
 *
 * @property RepositoryInterface<R> $distant
 */
abstract class OneOrMany extends Relation
{
    /**
     * Default attributes to fill when creating a new relation entity
     *
     * @var array<string, mixed>|null
     */
    private ?array $defaultValues = null;

    /**
     * {@inheritdoc}
     */
    public function relationRepository(): RepositoryInterface
    {
        return $this->distant;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyConstraints(ReadCommandInterface $query, iterable|callable $constraints = [], ?string $context = null): ReadCommandInterface
    {
        parent::applyConstraints($query, $constraints, $context);

        // Si le dépot distant possède la foreign key, on estime qu'il possède le discriminator
        // On applique donc la contrainte de relation sur le discriminitor
        if ($this->isPolymorphic() && $this->isForeignKeyBarrier($this->distant->entityClass())) {
            $query->where(
                $this->applyContext($context, [$this->discriminator => $this->discriminatorValue])
            );
        }

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function join(EntityJoinable $query, string $alias): void
    {
        $query->joinEntity($this->distant->entityName(), $this->distantKey, $this->getLocalAlias($query).$this->localKey, $alias);

        // apply relation constraints
        $this->applyConstraints($query, [], '$'.$alias);
    }

    /**
     * {@inheritdoc}
     */
    public function joinRepositories(EntityJoinable $query, string $alias, string|int|null $discriminator = null): array
    {
        return [
            $alias => $this->relationRepository()
        ];
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    protected function relations(array $keys, array $with, iterable|callable $constraints, array $without): array
    {
        /** @var R[] */
        return $this->relationQuery($keys, $constraints)
            ->with($with)
            ->without($without)
            ->all();
    }

    /**
     * {@inheritdoc}
     */
    protected function match(array $collection, array $relations): void
    {
        foreach ($relations as $key => $distant) {
            foreach ($collection[$key] as $local) {
                $this->setRelation($local, $distant);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function link(array|object $owner, ?string $queryClass = null): ReadCommandInterface
    {
        return $this->query($this->getLocalKeyValue($owner), [], $queryClass);
    }

    /**
     * {@inheritdoc}
     */
    public function associate(object $owner, object $entity): object
    {
        if (!$this->isForeignKeyBarrier($owner)) {
            throw new InvalidArgumentException('The local entity is not the foreign key barrier.');
        }

        if ($this->isPolymorphic()) {
            $this->discriminatorValue = $this->discriminator(get_class($entity));
        }

        $this->setForeignKeyValue($owner, $this->getDistantKeyValue($entity));
        $this->setRelation($owner, $entity);

        return $owner;
    }

    /**
     * {@inheritdoc}
     */
    public function dissociate(object $owner): object
    {
        if (!$this->isForeignKeyBarrier($owner)) {
            throw new InvalidArgumentException('The local entity is not the foreign key barrier.');
        }

        if ($this->isPolymorphic()) {
            $this->discriminatorValue = null;
        }

        // TODO Dont update key if it is embedded in the relation object
        $this->setForeignKeyValue($owner, null);
        $this->setRelation($owner, null);

        return $owner;
    }

    /**
     * {@inheritdoc}
     */
    public function create(object $owner, array $data = []): object
    {
        if ($this->isForeignKeyBarrier($owner)) {
            throw new InvalidArgumentException('The local entity is not the primary key barrier.');
        }

        $entity = $this->distant->entity($data);

        $this->fillDistantEntityConstraints($entity, $this->getLocalKeyValue($owner));

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function add(object $owner, object $related): int
    {
        if ($this->isForeignKeyBarrier($owner)) {
            throw new InvalidArgumentException('The local entity is not the primary key barrier.');
        }

        $this->fillDistantEntityConstraints($related, $this->getLocalKeyValue($owner));

        return $this->distant->save($related);
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function saveAll(object $owner, array $relations = []): int
    {
        $entities = $this->getRelation($owner);

        if (empty($entities)) {
            return 0;
        }

        $id = $this->getLocalKeyValue($owner);

        //Detach all relations
        if ($this->saveStrategy === self::SAVE_STRATEGY_REPLACE) {
            $this->query($id)->delete();
        }

        if (!is_array($entities)) {
            $entities = [$entities];
        }

        // Save new relations
        $nb = 0;

        foreach ($entities as $entity) {
            $this->fillDistantEntityConstraints($entity, $id);
            $nb += $this->distant->saveAll($entity, $relations);
        }

        return $nb;
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function deleteAll(object $owner, array $relations = []): int
    {
        $entities = $this->getRelation($owner);

        if (empty($entities)) {
            return 0;
        }

        if (!is_array($entities)) {
            $entities = [$entities];
        }

        $nb = 0;

        foreach ($entities as $entity) {
            $nb += $this->distant->deleteAll($entity, $relations);
        }

        return $nb;
    }

    /**
     * Get the repository that owns the foreign key and the key name
     *
     * @return array{0:RepositoryInterface,1:string}
     */
    abstract protected function getForeignInfos(): array;

    /**
     * Get the query used to load relations
     *
     * @param array $keys The owner keys
     * @param iterable<string,mixed>|callable $constraints Constraints to apply on the query
     *
     * @return ReadCommandInterface
     */
    abstract protected function relationQuery(array $keys, iterable|callable $constraints): ReadCommandInterface;

    /**
     * Check if the entity is the foreign key barrier
     *
     * @param class-string|object $entity
     *
     * @return bool
     */
    private function isForeignKeyBarrier(string|object $entity): bool
    {
        [$repository] = $this->getForeignInfos();

        if (!is_string($entity)) {
            $entity = $entity::class;
        }

        return $repository->entityClass() === $entity;
    }

    /**
     * Set the foreign key value on an entity
     *
     * @param object $entity
     * @param mixed  $id
     */
    private function setForeignKeyValue(object $entity, mixed $id): void
    {
        /**
         * @var RepositoryInterface $repository
         * @var string $key
         */
        list($repository, $key) = $this->getForeignInfos();

        if ($repository->entityClass() === get_class($entity)) {
            $repository->mapper()->hydrateOne($entity, $key, $id);

            if ($this->isPolymorphic()) {
                $repository->mapper()->hydrateOne($entity, $this->discriminator, $this->discriminatorValue);
            }
        }
    }

    /**
     * Fill the distant entity with default values and foreign key
     *
     * @param R $entity The distant entity
     * @param mixed $id The foreign key value
     *
     * @return void
     */
    private function fillDistantEntityConstraints(object $entity, mixed $id): void
    {
        $this->setForeignKeyValue($entity, $id);

        if (!$this->constraints || !$this->distant || !is_array($this->constraints)) {
            return;
        }

        if (($values = $this->defaultValues) === null) {
            $values = $this->defaultValues = array_intersect_key(
                $this->constraints,
                $this->distant->metadata()->attributes
            );
        }

        $mapper = $this->distant->mapper();

        foreach ($values as $key => $value) {
            $mapper->hydrateOne($entity, $key, $value);
        }
    }
}
