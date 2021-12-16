<?php

namespace Bdf\Prime\Relations\Info;

/**
 * Store loading information into an array, using spl_object_hash for identify entities
 */
final class LocalHashTableRelationInfo implements RelationInfoInterface
{
    /**
     * Store loaded state of entities relations
     * Use the entity object id as key, and boolean as value
     *
     * @var array<int, boolean>
     */
    private $loaded = [];

    /**
     * {@inheritdoc}
     */
    public function isLoaded($entity): bool
    {
        return !empty($this->loaded[spl_object_id($entity)]);
    }

    /**
     * {@inheritdoc}
     */
    public function clear($entity): void
    {
        unset($this->loaded[spl_object_id($entity)]);
    }

    /**
     * {@inheritdoc}
     */
    public function markAsLoaded($entity): void
    {
        // @todo que faire avec les stdClass ?
        $this->loaded[spl_object_id($entity)] = true;
    }
}
