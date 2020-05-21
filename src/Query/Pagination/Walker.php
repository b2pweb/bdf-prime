<?php

namespace Bdf\Prime\Query\Pagination;

use Bdf\Prime\Collection\CollectionInterface;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Query\ReadCommandInterface;

/**
 * Query Walker
 * 
 * Permet de parcourir des collections contenant de gros volume d'entités.
 * Le parcourt se fait par paquet d'entités définis par la limit de la query
 * Une fois la limite atteinte, la classe lance la requête suivante
 * 
 * Attention, le walker ne gère pas les objects collection
 *
 * @author  Seb
 * @package Bdf\Prime\Query\Pagination
 */
class Walker extends AbstractPaginator implements \Iterator, PaginatorInterface
{
    const DEFAULT_PAGE  = 1;
    const DEFAULT_LIMIT = 150;
    
    /**
     * First page
     * 
     * @var int
     */
    protected $startPage;

    /**
     * The current offset
     *
     * @var int
     */
    protected $offset;

    /**
     * Create a query walker
     * 
     * @param ReadCommandInterface $query
     * @param int            $maxRows
     * @param int            $page
     */
    public function __construct(ReadCommandInterface $query, $maxRows = null, $page = null)
    {
        $this->query = $query;
        $this->page = 0;
        $this->maxRows = $maxRows ?: self::DEFAULT_LIMIT;
        $this->startPage = $page ?: self::DEFAULT_PAGE;
    }

    /**
     * Load the first page of collection
     */
    public function load()
    {
        $this->page = $this->startPage;
        $this->loadCollection();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function loadCollection()
    {
        parent::loadCollection();
        
        if ($this->collection instanceof CollectionInterface) {
            $this->collection = $this->collection->all();
        }

        // Test if the collection has numerical keys.
        // We have to add the offset to the numerical key.
        if (isset($this->collection[0])) {
            $this->offset = ($this->page - $this->startPage) * $this->maxRows;
        } else {
            $this->offset = null;
        }
    }

    /**
     * SPL - Iterator
     *
     * {@inheritdoc}
     */
    public function current()
    {
        return current($this->collection);
    }
    
    /**
     * SPL - Iterator
     *
     * {@inheritdoc}
     */
    public function key()
    {
        if ($this->offset !== null) {
            return $this->offset + key($this->collection);
        }

        return key($this->collection);
    }
    
    /**
     * SPL - Iterator
     *
     * {@inheritdoc}
     */
    public function next()
    {
        if (false === next($this->collection)) {
            $this->page++;
            $this->loadCollection();
        }
    }
    
    /**
     * SPL - Iterator
     *
     * {@inheritdoc}
     */
    public function valid()
    {
        return false !== current($this->collection);
    }
    
    /**
     * SPL - Iterator
     *
     * {@inheritdoc}
     */
    public function rewind()
    {
        if (($this->page == $this->startPage) && count($this->collection)) {
            reset($this->collection);
        } else {
            $this->page = $this->startPage;
            $this->loadCollection();
        }
    }

    /**
     * {@inheritdoc}
     * 
     * l'iterator force le count sql
     */
    protected function buildSize()
    {
        $this->size = $this->query->paginationCount();
    }
}