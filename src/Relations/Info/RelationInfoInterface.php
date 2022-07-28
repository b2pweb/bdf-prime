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
     * @deprecated Will be removed in 3.0
     */
    public function clear($entity): void;

    /**
     * @param E $entity
     * @return void
     */
    public function markAsLoaded($entity): void;
}
