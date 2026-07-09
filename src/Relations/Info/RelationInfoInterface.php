<?php

namespace Bdf\Prime\Relations\Info;

/**
 * Interface RelationInfoInterface
 *
 * @template E as object
 */
interface RelationInfoInterface
{
    /**
     * @param E $entity
     * @return bool
     */
    public function isLoaded($entity): bool;

    /**
     * @param E $entity
     * @return void
     */
    public function clear($entity): void;

    /**
     * @param E $entity
     * @return void
     */
    public function markAsLoaded($entity): void;
}
