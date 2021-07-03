<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\Collection\Indexer\EntityIndexerInterface;
use Bdf\Prime\Query\Contract\EntityJoinable;
use Bdf\Prime\Query\Expression\Attribute;
use Bdf\Prime\Query\JoinClause;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Query\ReadCommandInterface;
use Bdf\Prime\Relations\Util\SimpleTableJoinRelation;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * Class ForeignInRelation
 */
class ForeignInRelation extends AbstractRelation implements CustomRelationInterface
{
    use SimpleTableJoinRelation;

    /**
     * @var string[]
     */
    private $localKeys;

    /**
     * @var string
     */
    private $distantKey;


    /**
     * ForeignInRelation constructor.
     *
     * @param $attributeAim
     * @param RepositoryInterface $local
     * @param $localKeys
     * @param RepositoryInterface $distant
     * @param $distantKey
     */
    public function __construct($attributeAim, RepositoryInterface $local, array $localKeys, RepositoryInterface $distant, $distantKey)
    {
        parent::__construct($attributeAim, $local, $distant);

        $this->localKeys = $localKeys;
        $this->distantKey = $distantKey;
    }

    /**
     * {@inheritdoc}
     */
    protected function buildJoinClause(JoinClause $clause, $query, $alias)
    {
        foreach ($this->localKeys as $key) {
            $clause->orOn($alias.'>'.$this->distantKey, '=', new Attribute($this->getLocalAlias($query).$key));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function joinRepositories(EntityJoinable $query, string $alias, $discriminator = null): array
    {
        return [
            $alias => $this->relationRepository()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(EntityIndexerInterface $collection, array $with = [], $constraints = [], array $without = []): void
    {
        $entities = $this
            ->query($this->getLocalKeyValue($collection->all()), $constraints)
            ->with($with)
            ->without($without)
            ->by($this->distantKey)
            ->all()
        ;

        foreach ($collection->all() as $owner) {
            $keys = $this->getLocalKeyValue($owner);

            $this->setRelation(
                $owner,
                array_map(
                    function ($key) use($entities) { return $entities[$key]; },
                    $keys
                )
            );
        }
    }

    /**
     * @param object|object[] $entity
     *
     * @return string[]|string[][]
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

        return $keys;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyWhereKeys(ReadCommandInterface $query, $value): ReadCommandInterface
    {
        if (is_array($value[0])) {
            $value = array_merge(...$value);
        }

        return $query->where($this->distantKey, 'in', $value);
    }

    /**
     * {@inheritdoc}
     */
    public static function make(RepositoryInterface $repository, string $relationName, array $relationMeta): RelationInterface
    {
        return new ForeignInRelation(
            $relationName,
            $repository,
            $relationMeta['localKeys'],
            $repository->repository($relationMeta['entity']),
            $relationMeta['distantKey']
        );
    }
}
