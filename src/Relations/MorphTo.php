<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\Collection\Indexer\EntityIndexer;
use Bdf\Prime\Collection\Indexer\EntityIndexerInterface;
use Bdf\Prime\Query\Contract\EntityJoinable;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Contract\WriteOperation;
use Bdf\Prime\Query\ReadCommandInterface;
use InvalidArgumentException;
use LogicException;

/**
 * MorphTo
 */
class MorphTo extends BelongsTo
{
    /**
     * {@inheritdoc}
     *
     * The alias should have #[sub entity] at its end
     */
    public function join(EntityJoinable $query, string $alias): void
    {
        $parts = explode('#', $alias);

        if (!isset($parts[1])) {
            throw new LogicException('Joins are not supported on polymorph without discriminator');
        }

        $this->loadDistantFromType(end($parts));

        //TODO should be in join clause
        $query->where($this->getLocalAlias($query).$this->discriminator, $this->discriminatorValue);

        // Join to the real alias (i.e. without the discriminator)
        parent::join($query, $parts[0]);
    }

    /**
     * {@inheritdoc}
     */
    public function joinRepositories(EntityJoinable $query, string $alias, $discriminator = null): array
    {
        if ($discriminator === null) {
            throw new LogicException('Joins are not supported on polymorph without discriminator');
        }

        $this->loadDistantFromType($discriminator);

        return [
            $alias => $this->relationRepository()
        ];
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function load(EntityIndexerInterface $collection, array $with = [], $constraints = [], array $without = []): void
    {
        if ($collection->empty()) {
            return;
        }

        $with = $this->rearrangeWith($with);
        $without = $this->rearrangeWithout($without);

        foreach ($collection->by($this->discriminator) as $type => $chunk) {
            $this->loadDistantFromType($type);

            // Relation fk or discriminator is null : ignore the relation (same behavior as other relations)
            if ($this->distant === null || $this->distantKey === null) {
                continue;
            }

            parent::load(
                EntityIndexer::fromArray($this->local->mapper(), $chunk),
                $with[$type],
                $constraints,
                $without[$type]
            );
        }
    }

    /**
     * {@inheritdoc}
     *
     * @fixme Do not works with EntityCollection
     */
    public function link($owner): ReadCommandInterface
    {
        if (is_array($owner)) {
            throw new InvalidArgumentException('MorphTo relation do not supports querying on collection');
        }

        $this->loadDistantFrom($owner);

        if ($this->discriminatorValue === null) {
            throw new InvalidArgumentException('The discriminator is missing on the owner entity');
        }

        return parent::link($owner);
    }

    /**
     * {@inheritdoc}
     */
    public function associate($owner, $entity)
    {
        $this->loadDistantFromType($this->discriminator(get_class($entity)));

        return parent::associate($owner, $entity);
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function saveAll($owner, array $relations = []): int
    {
        $relations = $this->rearrangeWith($relations);
        $this->loadDistantFrom($owner);

        // No discriminator on the owner : there is no relation
        if ($this->discriminatorValue === null) {
            return 0;
        }

        return parent::saveAll($owner, $relations[$this->discriminatorValue]);
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function deleteAll($owner, array $relations = []): int
    {
        $relations = $this->rearrangeWith($relations);
        $this->loadDistantFrom($owner);

        // No discriminator on the owner : there is no relation
        if ($this->discriminatorValue === null) {
            return 0;
        }

        return parent::deleteAll($owner, $relations[$this->discriminatorValue]);
    }

    /**
     * Change the current discriminator value 
     * and update the distant repository of this relation
     *
     * @param mixed $type
     *
     * @return void
     */
    protected function loadDistantFromType($type): void
    {
        $this->discriminatorValue = $type;

        $this->updateDistantInfos();
    }

    /**
     * Change the current discriminator value from the entity
     * and update the distant repository of this relation
     *
     * @param object $entity
     *
     * @return void
     */
    protected function loadDistantFrom($entity): void
    {
        $this->updateDiscriminatorValue($entity);

        $this->updateDistantInfos();
    }

    /**
     * Update the distant repository of this relation
     *
     * @return void
     */
    protected function updateDistantInfos(): void
    {
        $infos = $this->map($this->discriminatorValue);

        if ($infos) {
            $this->distant = $this->local->repository($infos['entity']);
            $this->distantKey = $infos['distantKey'];
            $this->setConstraints($infos['constraints'] ?? []);
        } else {
            $this->distant = null;
            $this->distantKey = null;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function relationQuery($keys, $constraints): ReadCommandInterface
    {
        return $this->query($keys, $constraints)->by($this->distantKey);
    }
}
