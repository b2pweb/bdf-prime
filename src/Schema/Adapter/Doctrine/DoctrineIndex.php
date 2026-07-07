<?php

namespace Bdf\Prime\Schema\Adapter\Doctrine;

use Bdf\Prime\Schema\IndexInterface;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Index\IndexedColumn;
use Doctrine\DBAL\Schema\Index\IndexType;

use function array_map;
use function sprintf;
use function trigger_error;

/**
 * Adapt doctrine index to prime index
 */
final readonly class DoctrineIndex implements IndexInterface
{
    public function __construct(
        private Index $index,
        private bool $canBePrimary = true,
    ) {
        if ($canBePrimary) {
            @trigger_error(sprintf('Use of %s to store primery key is deprecated. Use %s instead.', self::class, DoctrinePrimaryKeyIndex::class), E_USER_DEPRECATED);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return $this->index->getObjectName()->toString();
    }

    /**
     * {@inheritdoc}
     */
    public function unique(): bool
    {
        return $this->index->getType() === IndexType::UNIQUE;
    }

    /**
     * {@inheritdoc}
     */
    public function primary(): bool
    {
        return $this->canBePrimary && $this->index->isPrimary();
    }

    /**
     * {@inheritdoc}
     */
    public function type(): int
    {
        if ($this->canBePrimary && $this->index->isPrimary()) {
            return self::TYPE_PRIMARY;
        }

        if ($this->index->getType() === IndexType::UNIQUE) {
            return self::TYPE_UNIQUE;
        }

        return self::TYPE_SIMPLE;
    }

    /**
     * {@inheritdoc}
     */
    public function fields(): array
    {
        return array_map(static fn (IndexedColumn $col) => $col->getColumnName()->toString(), $this->index->getIndexedColumns());
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
        $options = [];

        $lengths = [];
        $hasLengths = false;

        foreach ($this->index->getIndexedColumns() as $column) {
            $lengths[] = $len = $column->getLength();

            if ($len !== null) {
                $hasLengths = true;
            }
        }

        if ($hasLengths) {
            $options['lengths'] = $lengths;
        }

        if ($this->index->isClustered()) {
            $options['clustered'] = true;
        }

        switch ($this->index->getType()) {
            case IndexType::FULLTEXT:
                $options['fulltext'] = true;
                break;

            case IndexType::SPATIAL:
                $options['spacial'] = true;
                break;
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function fieldOptions(string $field): array
    {
        $options = [];

        foreach ($this->index->getIndexedColumns() as $indexedColumn) {
            if ($indexedColumn->getColumnName()->toString() === $field) {
                if (($length = $indexedColumn->getLength()) !== null) {
                    $options['length'] = $length;
                }
                break;
            }
        }

        return $options;
    }
}
