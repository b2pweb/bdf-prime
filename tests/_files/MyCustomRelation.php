<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\Collection\Indexer\EntityIndexerInterface;
use Bdf\Prime\Query\Expression\Attribute;
use Bdf\Prime\Query\JoinClause;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Query\ReadCommandInterface;
use Bdf\Prime\Relations\Util\EntityKeys;
use Bdf\Prime\Relations\Util\SimpleTableJoinRelation;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * Class MyCustomRelation
 */
class MyCustomRelation extends AbstractRelation implements CustomRelationInterface
{
    use SimpleTableJoinRelation;

    /**
     * @var string[]
     */
    private $localKeys;

    /**
     * @var string[]
     */
    private $distantKeys;

    /**
     * MyCustomRelation constructor.
     *
     * @param $attributeAim
     * @param RepositoryInterface $local
     * @param $localKeys
     * @param RepositoryInterface $distant
     * @param $distantKeys
     */
    public function __construct($attributeAim, RepositoryInterface $local, $localKeys, RepositoryInterface $distant, $distantKeys)
    {
        parent::__construct($attributeAim, $local, $distant);

        $this->localKeys = $localKeys;
        $this->distantKeys = $distantKeys;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocalKey()
    {
        return $this->localKeys[0];
    }

    /**
     * {@inheritdoc}
     */
    public function load(EntityIndexerInterface $collection, array $with = [], $constraints = [], array $without = []): void
    {
        if (empty($collection)) {
            return;
        }

        $keys = [];

        foreach ($collection->all() as $entity) {
            $keys[] = $this->getLocalKeyValue($entity);
        }

        $this->match($keys, $this->relations($keys, $with, $constraints, $without));
    }

    /**
     * {@inheritdoc}
     */
    protected function buildJoinClause(JoinClause $clause, $query, $alias)
    {
        foreach ($this->distantKeys as $position => $key) {
            $clause->on($alias.'>'.$key, '=', new Attribute($this->getLocalAlias($query).$this->localKeys[$position]));
        }
    }

    /**
     * @param object|object[] $entity
     *
     * @return EntityKeys|EntityKeys[]
     */
    protected function getLocalKeyValue($entity)
    {
        if (is_array($entity)) {
            return array_map([$this, 'getLocalKeyValue'], $entity);
        }

        $keys = [];

        foreach ($this->localKeys as $key) {
            $keys[] = $this->local->extractOne($entity, $key);
        }

        return new EntityKeys($keys, $entity);
    }

    /**
     * @param object $entity
     *
     * @return EntityKeys
     */
    protected function getDistantKeyValue($entity)
    {
        $keys = [];

        foreach ($this->distantKeys as $key) {
            $keys[] = $this->distant->extractOne($entity, $key);
        }

        return new EntityKeys($keys, $entity);
    }

    protected function relations($keys, $with, $constraints, $without)
    {
        $relations = $this->query($keys, $constraints)
            ->with($with)
            ->without($without)
            ->all()
        ;

        $indexed = [];

        foreach ($relations as $entity) {
            $keys = $this->getDistantKeyValue($entity);

            if (isset($indexed[$keys->hash()])) {
                $indexed[$keys->hash()][] = $keys;
            } else {
                $indexed[$keys->hash()] = [$keys];
            }
        }

        return $indexed;
    }

    /**
     * @param EntityKeys[] $collection
     * @param EntityKeys[][] $relations
     */
    protected function match($collection, $relations)
    {
        foreach ($collection as $entity) {
            $found = false;

            if (isset($relations[$entity->hash()])) {
                foreach ($relations[$entity->hash()] as $matching) {
                    if ($entity->equals($matching)) {
                        $found = true;
                        $this->setRelation($entity->get(), $matching->get());
                        break;
                    }
                }
            }

            if (!$found) {
                $this->setRelation($entity->get(), null);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function applyWhereKeys(ReadCommandInterface $query, $value): ReadCommandInterface
    {
        if (!is_array($value)) {
            $query->where(array_combine($this->distantKeys, $value->toArray()));
        } else {
            foreach ($value as $keys) {
                $query->orWhere(array_combine($this->distantKeys, $keys->toArray()));
            }
        }

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public static function make(RepositoryInterface $repository, string $relationName, array $relationMeta): RelationInterface
    {
        return new MyCustomRelation(
            $relationName,
            $repository,
            array_keys($relationMeta['keys']),
            $repository->repository($relationMeta['entity']),
            array_values($relationMeta['keys'])
        );
    }
}
