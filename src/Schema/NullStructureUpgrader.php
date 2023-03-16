<?php

namespace Bdf\Prime\Schema;

/**
 * Null object for structure upgrader
 */
class NullStructureUpgrader implements StructureUpgraderInterface
{
    /**
     * {@inheritdoc}
     */
    public function migrate(bool $listDrop = true): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function diff(bool $listDrop = true): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function queries(bool $listDrop = true): array
    {
        return [
            'up' => [],
            'down' => [],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function truncate(bool $cascade = false): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function drop(): bool
    {
        return true;
    }
}
