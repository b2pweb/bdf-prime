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
    private static ?NullRelationInfo $instance = null;

    /**
     * {@inheritdoc}
     */
    public function isLoaded(object $entity): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(object $entity): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function markAsLoaded(object $entity): void
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
