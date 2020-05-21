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
    public function migrate($listDrop = true)
    {
        
    }
    
    /**
     * {@inheritdoc}
     */
    public function diff($listDrop = true)
    {
        return [];
    }
    
    /**
     * {@inheritdoc}
     */
    public function truncate($cascade = false)
    {
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function drop()
    {
        return true;
    }
}
