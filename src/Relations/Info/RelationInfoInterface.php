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
    public function isLoaded($entity);

    /**
     * @param E $entity
     * @return void
     */
    public function clear($entity);

    /**
     * @param E $entity
     * @return void
     */
    public function markAsLoaded($entity);
}
