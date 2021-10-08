<?php

namespace Bdf\Prime\Connection\Result\FetchStrategy;

/**
 * Fetch all result as list of columns (i.e. numeric array)
 *
 * Use `ListArrayFetch::instance()` instead of calling constructor
 *
 * @implements ArrayFetchStrategyInterface<list<mixed>>
 *
 * @psalm-immutable
 */
final class ListArrayFetch implements ArrayFetchStrategyInterface
{
    /**
     * @var ListArrayFetch
     * @readonly
     */
    private static $instance;

    /**
     * {@inheritdoc}
     */
    public function one(array $row)
    {
        return array_values($row);
    }

    /**
     * {@inheritdoc}
     */
    public function all(array $rows): array
    {
        $fetched = [];

        foreach ($rows as $row) {
            $fetched[] = array_values($row);
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
