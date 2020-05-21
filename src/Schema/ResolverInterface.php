<?php

namespace Bdf\Prime\Schema;

/**
 * @package Bdf\Prime\Schema
 */
interface ResolverInterface
{
    /**
     * Migrate table structure changes to database
     *
     * @param bool $listDrop
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function migrate($listDrop = true);
    
    /**
     * List table structure changes
     *
     * @param bool $listDrop
     *
     * @return array Array of queries
     */
    public function diff($listDrop = true);
    
    /**
     * Truncate table
     * 
     * @param bool $cascade
     *
     * @return bool 
     */
    public function truncate($cascade = false);
    
    /**
     * Drop table and its sequence if exists
     * 
     * @return bool 
     */
    public function drop();
}
