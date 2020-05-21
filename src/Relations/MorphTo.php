<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\Collection\Indexer\EntityIndexer;
use Bdf\Prime\Collection\Indexer\EntityIndexerInterface;
use Bdf\Prime\Query\Contract\EntityJoinable;

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
    public function join($query, $alias = null)
    {
        $parts = explode('#', (string)$alias);

        if (!isset($parts[1])) {
            throw new \LogicException('Joins are not supported on polymorph without discriminator');
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
    public function joinRepositories(EntityJoinable $query, $alias = null, $discriminatorValue = null)
    {
        $this->loadDistantFromType($discriminatorValue);

        return [
            $alias => $this->relationRepository()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(EntityIndexerInterface $collection, array $with = [], $constraints = [], array $without = [])
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
     *
     * @param object $owner
     */
    public function link($owner)
    {
        $this->loadDistantFrom($owner);

        return parent::link($owner);
    }

    /**
     * {@inheritdoc}
     */
    public function saveAll($owner, array $relations = [])
    {
        $relations = $this->rearrangeWith($relations);
        $this->loadDistantFrom($owner);

        return parent::saveAll($owner, $relations[$this->discriminatorValue]);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll($owner, array $relations = [])
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
    protected function relationQuery($keys, $constraints)
    {
        return $this->query($keys, $constraints)->by($this->distantKey);
    }
}
