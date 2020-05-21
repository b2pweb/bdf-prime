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
     * @return IndexInterface[]
     */
    public function added();

    /**
     * Get list of changed indexes
     *
     * @return IndexInterface[]
     */
    public function changed();

    /**
     * Get list of removed indexes
     *
     * @return IndexInterface[]
     */
    public function removed();
}
