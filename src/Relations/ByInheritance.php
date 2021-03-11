<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\Collection\Indexer\EntityIndexer;
use Bdf\Prime\Collection\Indexer\EntityIndexerInterface;
use Bdf\Prime\Mapper\SingleTableInheritanceMapper;
use Bdf\Prime\Query\Contract\EntityJoinable;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Contract\WriteOperation;
use Bdf\Prime\Query\ReadCommandInterface;
use Bdf\Prime\Relations\Util\ForeignKeyRelation;
use Bdf\Prime\Repository\RepositoryInterface;
use LogicException;

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
    public function __construct(string $attributeAim, RepositoryInterface $local, string $localKey)
    {
        parent::__construct($attributeAim, $local);

        $this->localKey = $localKey;

        $mapper = $local->mapper();
        
        if (!$mapper instanceof SingleTableInheritanceMapper) {
            throw new LogicException('The mapper could not manage single table inheritance relation');
        }

        $this->setDiscriminator($mapper->getDiscriminatorColumn());
        $this->setMap($mapper->getEntityMap());
    }

    /**
     * {@inheritdoc}
     */
    public function relationRepository(): RepositoryInterface
    {
        return $this->subRelation()->relationRepository();
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
    public function link($owner): ReadCommandInterface
    {
        $this->updateDiscriminatorValue($owner);

        return $this->subRelation()->link($owner);
    }

    /**
     * {@inheritdoc}
     */
    public function join(EntityJoinable $query, string $alias): void
    {
        $parts = explode('#', $alias);

        if (!isset($parts[1])) {
            throw new LogicException('Joins are not supported on polymorph without discriminator');
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
    public function joinRepositories(EntityJoinable $query, string $alias, $discriminator = null): array
    {
        $this->discriminatorValue = $discriminator;

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
    #[WriteOperation]
    public function add($owner, $related)
    {
        $this->updateDiscriminatorValue($owner);

        return $this->subRelation()->add($owner, $related);
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function saveAll($owner, array $relations = []): int
    {
        $relations = $this->rearrangeWith($relations);
        $this->updateDiscriminatorValue($owner);

        return $this->subRelation()->saveAll($owner, $relations[$this->discriminatorValue]);
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function deleteAll($owner, array $relations = []): int
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
    protected function subRelation(): RelationInterface
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
    protected function applyWhereKeys(ReadCommandInterface $query, $value): ReadCommandInterface
    {
        return $query;
    }
}
