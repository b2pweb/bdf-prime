<?php

namespace Bdf\Prime\Connection\Result\FetchStrategy;

use Doctrine\DBAL\Result;

/**
 * Fetch all result as associative array
 *
 * Use `AssociativeDoctrineFetch::instance()` instead of calling constructor
 *
 * @implements DoctrineFetchStrategyInterface<array<string, mixed>>
 */
final class AssociativeDoctrineFetch implements DoctrineFetchStrategyInterface
{
    /**
     * @var AssociativeDoctrineFetch
     * @readonly
     */
    private static $instance;

    /**
     * {@inheritdoc}
     */
    public function one(Result $result)
    {
        return $result->fetchAssociative();
    }

    /**
     * {@inheritdoc}
     */
    public function all(Result $result): array
    {
        return $result->fetchAllAssociative();
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
