<?php

namespace Bdf\Prime\Schema;

use Bdf\Prime\Exception\PrimeException;

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
     * @throws PrimeException When migration fail
     */
    public function migrate($listDrop = true);

    /**
     * List table structure changes
     *
     * @param bool $listDrop
     *
     * @return array Array of queries
     * @throws PrimeException When diff fail
     */
    public function diff($listDrop = true);

    /**
     * Truncate table
     * 
     * @param bool $cascade
     *
     * @return bool
     * @throws PrimeException When truncate fail
     */
    public function truncate($cascade = false);

    /**
     * Drop table and its sequence if exists
     * 
     * @return bool
     * @throws PrimeException When drop fail
     */
    public function drop();
}
