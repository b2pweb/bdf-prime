<?php

namespace Bdf\Prime\Schema\Comparator;

use Bdf\Prime\Schema\IndexInterface;
use Bdf\Prime\Schema\IndexSetInterface;

/**
 * Compare index set
 *
 * /!\ The primary key is handle as it, and not as a named index.
 *     The primary key index name is not considered, so if its name changed, it will not result to a change / replace
 */
class IndexSetComparator implements IndexSetComparatorInterface
{
    /**
     * @var IndexSetInterface
     */
    private $from;

    /**
     * @var IndexSetInterface
     */
    private $to;


    /**
     * IndexSetComparator constructor.
     *
     * @param IndexSetInterface $from
     * @param IndexSetInterface $to
     */
    public function __construct(IndexSetInterface $from, IndexSetInterface $to)
    {
        $this->from = $from;
        $this->to   = $to;
    }

    /**
     * {@inheritdoc}
     */
    public function added()
    {
        $added = [];

        foreach ($this->to->all() as $index) {
            //Both contains a primary index
            if ($index->primary() && $this->from->primary() !== null) {
                continue;
            }

            // The index is present on the "from" index set
            if ($this->from->has($index->name())) {
                continue;
            }

            $added[] = $index;
        }

        return $added;
    }

    /**
     * {@inheritdoc}
     */
    public function changed()
    {
        $changed = [];

        foreach ($this->from->all() as $index) {
            $toIndex = null;

            // PRIMARY index should not be identified by its name
            if ($index->primary()) {
                $toIndex = $this->to->primary();
            } elseif ($this->to->has($index->name())) {
                $toIndex = $this->to->get($index->name());
            }

            if (!isset($toIndex)) {
                continue;
            }

            if (!$this->equals($index, $toIndex)) {
                $changed[] = $toIndex;
            }
        }

        return $changed;
    }

    /**
     * {@inheritdoc}
     */
    public function removed()
    {
        $removed = [];

        foreach ($this->from->all() as $index) {
            // PRIMARY index should not be identified by its name
            if ($index->primary()) {
                if (!$this->to->primary()) {
                    $removed[] = $index;
                }

                continue;
            }

            if (!$this->to->has($index->name())) {
                $removed[] = $index;
            }
        }

        return $removed;
    }

    /**
     * Check if the two index are equals
     *
     * @param IndexInterface $index1
     * @param IndexInterface $index2
     *
     * @return bool
     */
    private function equals(IndexInterface $index1, IndexInterface $index2)
    {
        if ($index1->fields() != $index2->fields() || $index1->type() != $index2->type() || $index1->options() != $index2->options()) {
            return false;
        }

        foreach ($index1->fields() as $field) {
            if ($index1->fieldOptions($field) != $index2->fieldOptions($field)) {
                return false;
            }
        }

        return true;
    }
}
