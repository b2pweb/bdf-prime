<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\Query\Custom\KeyValue\KeyValueQuery;

/**
 * HasOne
 * 
 * @package Bdf\Prime\Relations
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
     * @var KeyValueQuery
     */
    private $relationQuery;


    /**
     * {@inheritdoc}
     */
    protected function getForeignInfos()
    {
        return [$this->distant, $this->distantKey];
    }

    /**
     * {@inheritdoc}
     */
    protected function relationQuery($keys, $constraints)
    {
        // Constraints can be on relation attributes : builder must be used
        // @todo Handle "bulk select"
        if (count($keys) !== 1 || $constraints || $this->constraints) {
            return $this->query($keys, $constraints)->by($this->distantKey);
        }

        if ($this->relationQuery) {
            return $this->relationQuery->where($this->distantKey, $keys[0]);
        }

        $query = $this->distant->queries()->keyValue($this->distantKey, $keys[0]);

        if (!$query) {
            return $this->query($keys, $constraints)->by($this->distantKey);
        }

        return $this->relationQuery = $query->by($this->distantKey);
    }
}
