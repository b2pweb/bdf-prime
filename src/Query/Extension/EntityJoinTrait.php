<?php

namespace Bdf\Prime\Query\Extension;

use Bdf\Prime\Query\Contract\EntityJoinable;
use Bdf\Prime\Query\Contract\Joinable;
use Bdf\Prime\Query\Expression\Attribute;
use Closure;
use LogicException;

/**
 * trait for @see EntityJoinable
 */
trait EntityJoinTrait
{
    /**
     * @see EntityJoinable::joinEntity()
     */
    public function joinEntity($entity, $key, $foreign = null, $alias = null, $type = Joinable::INNER_JOIN)
    {
        if ($alias === null) {
            throw new LogicException('Alias is required for entiy join "'.$entity.'"');
        }

        if ($key instanceof Closure) {
            $this->join([$entity, $alias], $key, null, null, $type);

            return $this;
        }

        $this->join([$entity, $alias], $alias.'>'.$key, '=', new Attribute($foreign), $type);

        return $this;
    }

    /**
     * @see EntityJoinable::leftJoinEntity()
     */
    public function leftJoinEntity($entity, $key, $foreign = null, $alias = null)
    {
        return $this->joinEntity($entity, $key, $foreign, $alias, Joinable::LEFT_JOIN);
    }

    /**
     * @see EntityJoinable::rightJoinEntity()
     */
    public function rightJoinEntity($entity, $key, $foreign = null, $alias = null)
    {
        return $this->joinEntity($entity, $key, $foreign, $alias, Joinable::RIGHT_JOIN);
    }

    /**
     * @see Joinable::join()
     */
    abstract public function join($table, $key, $operator = null, $foreign = null, $type = Joinable::INNER_JOIN);
}
