<?php

namespace Bdf\Prime\Query\Extension;

use Bdf\Prime\Query\Contract\EntityJoinable;
use Bdf\Prime\Query\Contract\Joinable;
use Bdf\Prime\Query\Expression\Attribute;
use Bdf\Prime\Query\QueryInterface;
use LogicException;

/**
 * trait for @see EntityJoinable
 *
 * @psalm-require-implements EntityJoinable
 */
trait EntityJoinTrait
{
    /**
     * {@inheritdoc}
     *
     * @see EntityJoinable::joinEntity()
     */
    public function joinEntity(string $entity, string|callable $key, ?string $foreign = null, ?string $alias = null, string $type = Joinable::INNER_JOIN): static
    {
        if ($alias === null) {
            throw new LogicException('Alias is required for entity join "'.$entity.'"');
        }

        if (is_string($key)) {
            $this->join([$entity, $alias], $alias.'>'.$key, '=', new Attribute($foreign), $type);
        } else {
            $this->join([$entity, $alias], $key, null, null, $type);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see EntityJoinable::leftJoinEntity()
     */
    public function leftJoinEntity(string $entity, string|callable $key, ?string $foreign = null, ?string $alias = null): static
    {
        return $this->joinEntity($entity, $key, $foreign, $alias, Joinable::LEFT_JOIN);
    }

    /**
     * {@inheritdoc}
     *
     * @see EntityJoinable::rightJoinEntity()
     */
    public function rightJoinEntity(string $entity, string|callable $key, ?string $foreign = null, ?string $alias = null): static
    {
        return $this->joinEntity($entity, $key, $foreign, $alias, Joinable::RIGHT_JOIN);
    }

    /**
     * {@inheritdoc}
     *
     * @see Joinable::join()
     * @return $this
     */
    abstract public function join(string|QueryInterface|array $table, string|callable $key, ?string $operator = null, mixed $foreign = null, string $type = Joinable::INNER_JOIN);
}
