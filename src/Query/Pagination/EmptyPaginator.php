<?php

namespace Bdf\Prime\Query\Pagination;

use Bdf\Prime\Collection\ArrayCollection;

/**
 * A empty paginator
 * 
 * @author  Seb
 * @package Bdf\Prime\Query\Pagination
 */
class EmptyPaginator extends Paginator
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
    public function size()
    {
        return 0;
    }
    
    /**
     * {@inheritdoc}
     */
    public function order($attribute = null)
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function limit()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function offset()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function page()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function pageMaxRows()
    {
        return 0;
    }
}