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
    public function name(): ?string
    {
        return $this->index->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function unique(): bool
    {
        return $this->index->isUnique();
    }

    /**
     * {@inheritdoc}
     */
    public function primary(): bool
    {
        return $this->index->isPrimary();
    }

    /**
     * {@inheritdoc}
     */
    public function type(): int
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
    public function fields(): array
    {
        return $this->index->getColumns();
    }

    /**
     * {@inheritdoc}
     */
    public function isComposite(): bool
    {
        return count($this->fields()) > 1;
    }

    /**
     * {@inheritdoc}
     */
    public function options(): array
    {
        return array_merge(
            $this->index->getOptions(),
            array_fill_keys($this->index->getFlags(), true)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function fieldOptions(string $field): array
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
