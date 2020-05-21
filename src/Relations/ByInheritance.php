<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\Collection\Indexer\EntityIndexer;
use Bdf\Prime\Collection\Indexer\EntityIndexerInterface;
use Bdf\Prime\Mapper\SingleTableInheritanceMapper;
use Bdf\Prime\Query\Contract\EntityJoinable;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Relations\Util\ForeignKeyRelation;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * ByInheritance
 *
 * @todo options ?
 */
class ByInheritance extends AbstractRelation
{
    use Polymorph;
    use ForeignKeyRelation;

    /**
     * Set inheritance relation
     * 
     * @param string              $attributeAim
     * @param RepositoryInterface $local
     * @param string              $localKey
     */
    public function __construct($attributeAim, RepositoryInterface $local, $localKey)
    {
        parent::__construct($attributeAim, $local);

        $this->localKey = $localKey;

        $mapper = $local->mapper();
        
        if (!$mapper instanceof SingleTableInheritanceMapper) {
            throw new \LogicException('The mapper could not manage single table inheritance relation');
        }

        $this->setDiscriminator($mapper->getDiscriminatorColumn());
        $this->setMap($mapper->getEntityMap());
    }

    /**
     * {@inheritdoc}
     */
    public function relationRepository()
    {
        return $this->subRelation()->relationRepository();
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
            $this->discriminatorValue = $type;
            $subRelation = $this->subRelation();

            $subRelation->load(
                EntityIndexer::fromArray($subRelation->localRepository()->mapper(), $chunk),
                $with[$type],
                $constraints,
                $without[$type]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function link($owner)
    {
        $this->updateDiscriminatorValue($owner);

        return $this->subRelation()->associate($owner);
    }

    /**
     * {@inheritdoc}
     */
    public function join($query, $alias = null)
    {
        $parts = explode('#', (string)$alias);

        if (!isset($parts[1])) {
            throw new \LogicException('Joins are not supported on polymorph without discriminator');
        }

        $this->discriminatorValue = end($parts);

        //Use real alias
        $this->subRelation()->setLocalAlias($this->localAlias)->join($query, $parts[0]);

        //TODO should be in join clause
        $query->where($this->getLocalAlias($query).$this->discriminator, $this->discriminatorValue);
    }

    /**
     * {@inheritdoc}
     */
    public function joinRepositories(EntityJoinable $query, $alias = null, $discriminatorValue = null)
    {
        $this->discriminatorValue = $discriminatorValue;

        return [
            $alias => $this->relationRepository()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function associate($owner, $entity)
    {
        $this->updateDiscriminatorValue($owner);

        return $this->subRelation()->associate($owner, $entity);
    }

    /**
     * {@inheritdoc}
     */
    public function dissociate($owner)
    {
        $this->updateDiscriminatorValue($owner);

        return $this->subRelation()->dissociate($owner);
    }

    /**
     * {@inheritdoc}
     */
    public function create($owner, array $data = [])
    {
        $this->updateDiscriminatorValue($owner);

        return $this->subRelation()->create($owner, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function add($owner, $related)
    {
        $this->updateDiscriminatorValue($owner);

        return $this->subRelation()->saveAll($owner, $related);
    }

    /**
     * {@inheritdoc}
     */
    public function saveAll($owner, array $relations = [])
    {
        $relations = $this->rearrangeWith($relations);
        $this->updateDiscriminatorValue($owner);

        return $this->subRelation()->saveAll($owner, $relations[$this->discriminatorValue]);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll($owner, array $relations = [])
    {
        $relations = $this->rearrangeWith($relations);
        $this->updateDiscriminatorValue($owner);

        return $this->subRelation()->deleteAll($owner, $relations[$this->discriminatorValue]);
    }

    /**
     * Get the delagated sub relation
     *
     * @return RelationInterface
     */
    protected function subRelation()
    {
        $infos = $this->map($this->discriminatorValue);

        $relation = $this->local->repository($infos['entity'])
            ->relation($this->attributeAim);

        // TODO doit on redescendre les options sur la relation ?
        
        return $relation;
    }

    /**
     * Unused method by inheritance
     *
     * {@inheritdoc}
     */
    protected function relations($keys, $with, $constraints, $without)
    {

    }

    /**
     * Unused method by inheritance
     *
     * {@inheritdoc}
     */
    protected function match($collection, $relations)
    {

    }

    /**
     * {@inheritdoc}
     */
    protected function applyWhereKeys(QueryInterface $query, $value)
    {

    }
}
