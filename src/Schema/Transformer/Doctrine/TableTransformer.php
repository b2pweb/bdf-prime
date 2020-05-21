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
final class TableTransformer
{
    /**
     * @var TableInterface
     */
    private $table;

    /**
     * @var PlatformInterface
     */
    private $platform;


    /**
     * TableTransformer constructor.
     *
     * @param TableInterface $table
     * @param PlatformInterface $platform
     */
    public function __construct(TableInterface $table, PlatformInterface $platform)
    {
        $this->table = $table;
        $this->platform = $platform;
    }

    /**
     * Get the doctrine table
     *
     * @return Table
     */
    public function toDoctrine()
    {
        if ($this->table instanceof DoctrineTable) {
            return $this->table->toDoctrine();
        }

        $extractor = new FkExtractor();
        $this->table->constraints()->apply($extractor);

        return new Table(
            $this->table->name(),
            array_map(function (ColumnInterface $column) {
                return (new ColumnTransformer($column, $this->platform))
                    ->toDoctrine();
            }, $this->table->columns()),
            array_map(function (IndexInterface $index) {
                return (new IndexTransformer($index))
                    ->toDoctrine();
            }, $this->table->indexes()->all()),
            $extractor->all(),
            0,
            $this->table->options()
        );
    }
}
