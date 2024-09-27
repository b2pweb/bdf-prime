<?php

namespace Bdf\Prime\Relations\Info;

/**
 * Null object for storing relation information
 *
 * @template E as object
 * @implements RelationInfoInterface<E>
 */
final class NullRelationInfo implements RelationInfoInterface
{
    private static $instance;

    /**
     * {@inheritdoc}
     */
    public function isLoaded($entity): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function clear($entity): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function markAsLoaded($entity): void
    {
    }

    /**
     * @return NullRelationInfo
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            return self::$instance = new self();
        }

        return self::$instance;
    }
}
