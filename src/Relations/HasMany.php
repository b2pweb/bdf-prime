<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\Query\Custom\KeyValue\KeyValueQuery;
use Bdf\Prime\Query\ReadCommandInterface;

use function count;

/**
 * HasMany
 *
 * @template L as object
 * @template R as object
 *
 * @extends OneOrMany<L, R>
 */
class HasMany extends OneOrMany
{
    /**
     * {@inheritdoc}
     */
    protected $saveStrategy = self::SAVE_STRATEGY_REPLACE;

    /**
     * Store the relation query for optimisation purpose
     *
     * @var KeyValueQuery
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
    public function loadRecordByForeignKeys(array $keys, string $recordClass): array
    {
        $loaded = parent::loadRecordByForeignKeys($keys, $recordClass);

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
    protected function relationQuery($keys, $constraints, bool $recreate = false): ReadCommandInterface
    {
        // Constraints can be set on relation attributes : builder must be used
        // @todo Handle "bulk select"
        if (count($keys) !== 1 || $constraints || $this->constraints || $this->isPolymorphic()) {
            return $this->query($keys, $constraints)->by($this->distantKey, true);
        }

        if ($this->relationQuery && !$recreate) {
            return $this->relationQuery->where($this->distantKey, $keys[0]);
        }

        $query = $this->distant->queries()->keyValue($this->distantKey, $keys[0]);

        if (!$query) {
            return $this->query($keys, $constraints)->by($this->distantKey, true);
        }

        $query->by($this->distantKey, true);

        if (!$recreate) {
            $this->relationQuery = $query;
        }

        return $query;
    }
}
