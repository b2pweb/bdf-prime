<?php

namespace Bdf\Prime\Schema\Transformer\Doctrine;

use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Schema\Adapter\Doctrine\DoctrineTable;
use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Schema\IndexInterface;
use Bdf\Prime\Schema\TableInterface;
use Doctrine\DBAL\Schema\Table;

/**
 * Transform Prime table to doctrine table
 */
final readonly class TableTransformer
{
    public function __construct(
        private TableInterface $table,
    ) {
    }

    /**
     * Get the doctrine table
     */
    public function toDoctrine(): Table
    {
        if ($this->table instanceof DoctrineTable) {
            return $this->table->toDoctrine();
        }

        $extractor = new FkExtractor();
        $this->table->constraints()->apply($extractor);

        return new Table(
            $this->table->name(),
            array_map(
                static fn (ColumnInterface $column) => new ColumnTransformer($column)->toDoctrine(),
                $this->table->columns()
            ),
            array_map(
                static fn (IndexInterface $index) => new IndexTransformer($index)->toDoctrine(),
                $this->table->indexes()->secondaries()
            ),
            fkConstraints: $extractor->all(),
            options: $this->table->options(),
            primaryKeyConstraint: ($primary = $this->table->indexes()->primary())
                ? new IndexTransformer($primary)->toDoctrinePrimaryKey()
                : null,
        );
    }
}
