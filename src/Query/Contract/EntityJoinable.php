<?php

namespace Bdf\Prime\Query\Contract;

use Closure;
use LogicException;

/**
 * Interface for joinEntity() methods
 */
interface EntityJoinable extends Joinable, Whereable
{
    /**
     * Creates and adds a join to the query.
     *
     * <code>
     *     $query
     *         ->joinEntity('Namespace\\EntityClass', 'userId', 'id', 'entity')
     *         ->where('entity.name', ':like', 'seb%');
     * </code>
     *
     * @param class-string $entity
     * @param string|callable(\Bdf\Prime\Query\JoinClause):void $key
     * @param string|null $foreign
     * @param string|null $alias Alias is mandatory
     * @param Joinable::* $type Type of join.
     *
     * @return $this This Query instance.
     *
     * @throws LogicException  If alias is not set and $key is not a closure
     */
    public function joinEntity(string $entity, $key, ?string $foreign = null, string $alias = null, string $type = self::INNER_JOIN);

    /**
     * Creates and adds a join to the query.
     *
     * @param class-string $entity
     * @param string|callable(\Bdf\Prime\Query\JoinClause):void $key
     * @param string|null $foreign
     * @param string|null $alias
     *
     * @return $this This Query instance.
     */
    public function leftJoinEntity(string $entity, $key, ?string $foreign = null, string $alias = null);

    /**
     * Creates and adds a join to the query.
     *
     * @param class-string $entity
     * @param string|callable(\Bdf\Prime\Query\JoinClause):void $key
     * @param string|null $foreign
     * @param string|null $alias
     *
     * @return $this This Query instance.
     */
    public function rightJoinEntity(string $entity, $key, ?string $foreign = null, string $alias = null);
}
