<?php

namespace Bdf\Prime\Schema;

/**
 * Null Schema resolver
 */
class NullResolver implements ResolverInterface
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
