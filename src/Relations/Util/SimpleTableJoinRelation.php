<?php

namespace Bdf\Prime\Relations\Util;

use Bdf\Prime\Query\Contract\EntityJoinable;
use Bdf\Prime\Query\JoinClause;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Query\ReadCommandInterface;
use Bdf\Prime\Relations\AbstractRelation;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * Configure relation with simple join (one level) relation
 *
 * @property EntityRepository $distant protected
 * @property string $attributeAim protected
 */
trait SimpleTableJoinRelation
{
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
    public function link($owner): ReadCommandInterface
    {
        return $this->query($this->getLocalKeyValue($owner));
    }

    /**
     * {@inheritdoc}
     */
    public function join(EntityJoinable $query, string $alias): void
    {
        // @fixme ?
//        if ($alias === null) {
//            $alias = $this->attributeAim;
//        }

        $query->joinEntity(
            $this->distant->entityName(),
            function (JoinClause $clause) use ($alias, $query) {
                $this->buildJoinClause($clause, $query, $alias);
            },
            null,
            $alias
        );

        // apply relation constraints
        $this->applyConstraints($query, [], '$'.$alias);
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
     * @see AbstractRelation::query()
     */
    abstract protected function query($value, $constraints = []): ReadCommandInterface;

    /**
     * @see AbstractRelation::applyConstraints()
     */
    abstract protected function applyConstraints(ReadCommandInterface $query, $constraints = [], $context = null): ReadCommandInterface;

    /**
     * @see AbstractRelation::applyContext()
     */
    abstract protected function applyContext(?string $context, $constraints);

    /**
     * Extract local (owner) entity key(s)
     *
     * If an array of entity is provide, an array of keys must be returned like this (pseudo-code) :
     *
     * > function getLocalKeyValue (object $entity)     -> extractKey($entity)
     * > function getLocalKeyValue (object[] $entities) -> $entities->map(getLocalKeyValue(object))
     *
     * @param object|object[] $entity The entity, or array of entity
     *
     * @return mixed Can return any values that defined AbstractRelation::applyWhereKeys() supports
     */
    abstract protected function getLocalKeyValue($entity);

    /**
     * Configure the join clause
     *
     * Ex:
     *
     * <code>
     * protected function buildJoinClause(JoinClause $clause, QueryInterface $query, $alias)
     * {
     *     // $alias>$key : Check value of attribute $key into distant table ($alias is the alias of the distant table)
     *     // $this->getLocalAlias($query).$key : Get attribute $key on the local table (getLocalAlias resolve the local table alias)
     *     // new Attribute(...) : Compare with the attribute value instead of litteral string one
     *     $clause->on($alias.'>'.$this->distantKey, '=', new Attribute($this->getLocalAlias($query).$this->localKey));
     * }
     * </code>
     *
     * @param JoinClause $clause The clause to build
     * @param QueryInterface $query The base query
     * @param string $alias The distant table alias
     *
     * @return void
     */
    abstract protected function buildJoinClause(JoinClause $clause, $query, $alias);
}
