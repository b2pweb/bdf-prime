<?php

namespace Bdf\Prime\Schema\Comparator;

/**
 * Comparator for index set, which transform changed() to added() and remove() (do a replace of the index)
 * The changed() will always returns an empty array
 */
class ReplaceIndexSetComparator implements IndexSetComparatorInterface
{
    /**
     * @var IndexSetComparatorInterface
     */
    private $comparator;


    /**
     * ReplaceIndexSetComparator constructor.
     *
     * @param IndexSetComparatorInterface $comparator
     */
    public function __construct(IndexSetComparatorInterface $comparator)
    {
        $this->comparator = $comparator;
    }

    /**
     * {@inheritdoc}
     */
    public function added()
    {
        return array_merge(
            $this->comparator->added(),
            $this->comparator->changed()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function changed()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function removed()
    {
        return array_merge(
            $this->comparator->removed(),
            $this->comparator->changed()
        );
    }
}
