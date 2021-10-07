<?php

namespace Bdf\Prime\Connection\Result\FetchStrategy;

use Doctrine\DBAL\Result;

/**
 * Fetch all result as list of columns (i.e. numeric array)
 *
 * Use `ListDoctrineFetch::instance()` instead of calling constructor
 *
 * @implements DoctrineFetchStrategyInterface<list<mixed>>
 */
final class ListDoctrineFetch implements DoctrineFetchStrategyInterface
{
    /**
     * @var ListDoctrineFetch
     * @readonly
     */
    private static $instance;

    /**
     * {@inheritdoc}
     */
    public function one(Result $result)
    {
        return $result->fetchNumeric();
    }

    /**
     * {@inheritdoc}
     */
    public function all(Result $result): array
    {
        return $result->fetchAllNumeric();
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
