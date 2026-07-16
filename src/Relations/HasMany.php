<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\Query\Custom\KeyValue\KeyValueQuery;
use Bdf\Prime\Query\ReadCommandInterface;

/**
 * HasMany
 *
 * @template L as object
 * @template R as object
 *
 * @extends OneOrMany<L, R>
 */
final class HasMany extends OneOrMany
{
    /**
     * {@inheritdoc}
     */
    protected $saveStrategy = self::SAVE_STRATEGY_REPLACE;

    /**
     * Store the relation query for optimisation purpose
     */
    private ?KeyValueQuery $relationQuery = null;

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
    public function loadByForeignKeys(array $keys): array
    {
        $loaded = parent::loadByForeignKeys($keys);

        if (count($keys) === count($loaded)) {
            return $loaded;
        }

        // Provide empty array for missing keys
        foreach ($keys as $key) {
            $loaded[$key] ??= [];
        }

        return $loaded;
    }

    /**
     * {@inheritdoc}
     */
    protected function relationQuery(array $keys, iterable|callable $constraints): ReadCommandInterface
    {
        // Constraints can be on relation attributes : builder must be used
        // @todo Handle "bulk select"
        if (count($keys) !== 1 || $constraints || $this->constraints) {
            return $this->query($keys, $constraints)->by($this->distantKey, true);
        }

        if ($this->relationQuery) {
            return $this->relationQuery->where($this->distantKey, $keys[0]);
        }

        $query = $this->distant->queries()->keyValue($this->distantKey, $keys[0]);

        if (!$query) {
            return $this->query($keys, $constraints)->by($this->distantKey, true);
        }

        return $this->relationQuery = $query->by($this->distantKey, true);
    }
}
