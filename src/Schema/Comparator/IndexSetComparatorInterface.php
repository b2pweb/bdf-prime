<?php

namespace Bdf\Prime\Schema\Comparator;

use Bdf\Prime\Schema\IndexInterface;

/**
 * Compare index sets
 */
interface IndexSetComparatorInterface
{
    /**
     * Get list of added indexes
     *
     * @return list<IndexInterface>
     */
    public function added(): array;

    /**
     * Get list of changed indexes
     *
     * @return list<IndexInterface>
     */
    public function changed(): array;

    /**
     * Get list of removed indexes
     *
     * @return list<IndexInterface>
     */
    public function removed(): array;
}
