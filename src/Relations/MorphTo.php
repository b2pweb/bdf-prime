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

        return parent::link($owner);
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function saveAll($owner, array $relations = []): int
    {
        $relations = $this->rearrangeWith($relations);
        $this->loadDistantFrom($owner);

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

        return parent::deleteAll($owner, $relations[$this->discriminatorValue]);
    }

    /**
     * Change the current discriminator value 
     * and update the distant repository of this relation
     * 
     * @param mixed $type
     */
    protected function loadDistantFromType($type)
    {
        $this->discriminatorValue = $type;

        $this->updateDistantInfos();
    }

    /**
     * Change the current discriminator value from the entity
     * and update the distant repository of this relation
     * 
     * @param object $entity
     */
    protected function loadDistantFrom($entity)
    {
        $this->updateDiscriminatorValue($entity);

        $this->updateDistantInfos();
    }

    /**
     * Update the distant repository of this relation
     */
    protected function updateDistantInfos()
    {
        $infos = $this->map($this->discriminatorValue);

        $this->distant    = $this->local->repository($infos['entity']);
        $this->distantKey = $infos['distantKey'];

        $this->setConstraints(isset($infos['constraints']) ? $infos['constraints'] : []);
    }

    /**
     * {@inheritdoc}
     */
    protected function relationQuery($keys, $constraints): ReadCommandInterface
    {
        return $this->query($keys, $constraints)->by($this->distantKey);
    }
}
