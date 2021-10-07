<?php

namespace Bdf\Prime\Connection\Result\FetchStrategy;

/**
 * Fetch all result as associative array
 *
 * Use `AssociativeArrayFetch::instance()` instead of calling constructor
 *
 * @implements ArrayFetchStrategyInterface<array<string, mixed>>
 * @psalm-immutable
 */
final class AssociativeArrayFetch implements ArrayFetchStrategyInterface
{
    /**
     * @var AssociativeArrayFetch
     * @readonly
     */
    private static $instance;

    /**
     * {@inheritdoc}
     */
    public function one(array $row)
    {
        return $row;
    }

    /**
     * {@inheritdoc}
     */
    public function all(array $rows): array
    {
        return $rows;
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
