<?php

namespace Bdf\Prime\Connection\Result\FetchStrategy;

use stdClass;

/**
 * Fetch all result as a simple object (i.e. stdClass)
 *
 * Use `ObjectArrayFetch::instance()` instead of calling constructor
 *
 * @implements ArrayFetchStrategyInterface<stdClass>
 *
 * @psalm-immutable
 */
final class ObjectArrayFetch implements ArrayFetchStrategyInterface
{
    /**
     * @var ObjectArrayFetch
     * @readonly
     */
    private static $instance;

    /**
     * {@inheritdoc}
     */
    public function one(array $row)
    {
        return (object) $row;
    }

    /**
     * {@inheritdoc}
     */
    public function all(array $rows): array
    {
        $fetched = [];

        foreach ($rows as $row) {
            $fetched[] = (object) $row;
        }

        return $fetched;
    }

    /**
     * Get the strategy instance
     *
     * @return self
     */
    public static function instance(): self
    {
        if (!self::$instance) {
            return self::$instance = new self();
        }

        return self::$instance;
    }
}
