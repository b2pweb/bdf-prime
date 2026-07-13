<?php

namespace Bdf\Prime\Query\Pagination;

use Bdf\Prime\Collection\ArrayCollection;

/**
 * A empty paginator
 *
 * @template R as array|object
 * @extends Paginator<R>
 */
final class EmptyPaginator extends Paginator
{
    /**
     * Create an empty paginator
     */
    public function __construct()
    {
        $this->collection = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function size(): int
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function order($attribute = null)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function limit(): ?int
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function offset(): ?int
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function page(): int
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function pageMaxRows(): int
    {
        return 0;
    }
}
