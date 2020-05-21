<?php

namespace Bdf\Prime\Schema\Adapter\Doctrine;

use Bdf\Prime\Schema\IndexInterface;
use Doctrine\DBAL\Schema\Index;

/**
 * Adapt doctrine index to prime index
 */
final class DoctrineIndex implements IndexInterface
{
    /**
     * @var Index
     */
    private $index;


    /**
     * DoctrineIndex constructor.
     *
     * @param Index $index
     */
    public function __construct(Index $index)
    {
        $this->index = $index;
    }

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return $this->index->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function unique()
    {
        return $this->index->isUnique();
    }

    /**
     * {@inheritdoc}
     */
    public function primary()
    {
        return $this->index->isPrimary();
    }

    /**
     * {@inheritdoc}
     */
    public function type()
    {
        if ($this->index->isSimpleIndex()) {
            return self::TYPE_SIMPLE;
        }

        if ($this->index->isPrimary()) {
            return self::TYPE_PRIMARY;
        }

        return self::TYPE_UNIQUE;
    }

    /**
     * {@inheritdoc}
     */
    public function fields()
    {
        return $this->index->getColumns();
    }

    /**
     * {@inheritdoc}
     */
    public function isComposite()
    {
        return count($this->fields()) > 1;
    }

    /**
     * {@inheritdoc}
     */
    public function options()
    {
        return array_merge(
            $this->index->getOptions(),
            array_fill_keys($this->index->getFlags(), true)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function fieldOptions($field)
    {
        $options = [];

        if ($this->index->hasOption('lengths')) {
            $lengths = $this->index->getOption('lengths');
            $index = array_search($field, $this->fields());

            if ($index !== false && isset($lengths[$index])) {
                $options['length'] = $lengths[$index];
            }
        }

        return $options;
    }
}
