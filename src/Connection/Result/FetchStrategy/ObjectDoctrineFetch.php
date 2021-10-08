<?php

namespace Bdf\Prime\Connection\Result\FetchStrategy;

use Doctrine\DBAL\Result;
use stdClass;

/**
 * Fetch all result as a simple object (i.e. stdClass)
 *
 * Use `ObjectDoctrineFetch::instance()` instead of calling constructor
 *
 * @implements DoctrineFetchStrategyInterface<stdClass>
 */
final class ObjectDoctrineFetch implements DoctrineFetchStrategyInterface
{
    /**
     * @var ObjectDoctrineFetch
     * @readonly
     */
    private static $instance;

    /**
     * {@inheritdoc}
     */
    public function one(Result $result)
    {
        $value = $result->fetchAssociative();

        return $value ? (object) $value : false;
    }

    /**
     * {@inheritdoc}
     */
    public function all(Result $result): array
    {
        $rows = [];

        foreach ($result->fetchAllAssociative() as $row) {
            $rows[] = (object) $row;
        }

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
