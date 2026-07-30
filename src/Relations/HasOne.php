<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\Query\Custom\KeyValue\KeyValueQuery;
use Bdf\Prime\Query\ReadCommandInterface;

/**
 * HasOne
 *
 * @template L as object
 * @template R as object
 *
 * @extends OneOrMany<L, R>
 */
class HasOne extends OneOrMany
{
    /**
     * {@inheritdoc}
     */
    protected $saveStrategy = self::SAVE_STRATEGY_ADD;

    /**
     * Store the relation query for optimisation purpose
     *
     * @var KeyValueQuery<\Bdf\Prime\Connection\ConnectionInterface, R>|null
     */
    private $relationQuery;


    /**
     * {@inheritdoc}
     */
    protected function getForeignInfos(): array
    {
        return [$this->distant, $this->distantKey];
    }

    /**
     * {@inheritdoc}
     */
    protected function relationQuery($keys, $constraints, bool $recreate = false): ReadCommandInterface
    {
        // Constraints can be on relation attributes : builder must be used
        // @todo Handle "bulk select"
        if (count($keys) !== 1 || $constraints || $this->constraints || $this->isPolymorphic()) {
            return $this->query($keys, $constraints)->by($this->distantKey);
        }

        if ($this->relationQuery && !$recreate) {
            return $this->relationQuery->where($this->distantKey, $keys[0]);
        }

        $query = $this->distant->queries()->keyValue($this->distantKey, $keys[0]);

        if (!$query) {
            return $this->query($keys, $constraints)->by($this->distantKey);
        }

        $query->by($this->distantKey);

        if (!$recreate) {
            $this->relationQuery = $query;
        }

        return $query;
    }
}
