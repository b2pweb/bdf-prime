<?php

namespace Bdf\Prime\Relations\Info;

/**
 * Store loading information into an array, using WeakMap for identify entities
 */
final class LocalHashTableRelationInfo implements RelationInfoInterface
{
    /**
     * Store loaded state of entities relations
     * Use the entity object as key, and boolean as value
     *
     * @var \WeakMap<object, bool>
     */
    private $loaded;

    public function __construct()
    {
        $this->loaded = new \WeakMap();
    }

    /**
     * {@inheritdoc}
     */
    public function isLoaded($entity): bool
    {
        return !empty($this->loaded[$entity]);
    }

    /**
     * {@inheritdoc}
     */
    public function clear($entity): void
    {
        unset($this->loaded[$entity]);
    }

    /**
     * {@inheritdoc}
     */
    public function markAsLoaded($entity): void
    {
        $this->loaded[$entity] = true;
    }
}
