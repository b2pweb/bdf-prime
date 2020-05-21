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
     * @param string          $entity
     * @param string|\Closure  $key
     * @param string          $foreign
     * @param string          $alias    Alias is mandatory
     * @param string          $type     Type of join.
     *
     * @return $this This Query instance.
     *
     * @throws LogicException  If alias is not set and $key is not a closure
     */
    public function joinEntity($entity, $key, $foreign = null, $alias = null, $type = 'inner');


    /**
     * Creates and adds a join to the query.
     *
     * @param string|array $entity
     * @param string|Closure $key
     * @param string $foreign
     * @param string|null $alias
     *
     * @return $this This Query instance.
     */
    public function leftJoinEntity($entity, $key, $foreign = null, $alias = null);

    /**
     * Creates and adds a join to the query.
     *
     * @param string|array $entity
     * @param string|Closure $key
     * @param string $foreign
     * @param string|null $alias
     *
     * @return $this This Query instance.
     */
    public function rightJoinEntity($entity, $key, $foreign = null, $alias = null);
}
