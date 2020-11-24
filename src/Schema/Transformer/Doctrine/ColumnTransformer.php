<?php

namespace Bdf\Prime\Schema\Transformer\Doctrine;

use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Schema\ColumnInterface;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;

/**
 * Transform prime column to doctrine column
 */
final class ColumnTransformer
{
    /**
     * @var ColumnInterface
     */
    private $column;

    /**
     * @var PlatformInterface
     */
    private $platform;


    /**
     * ColumnTransformer constructor.
     *
     * @param ColumnInterface $column
     * @param PlatformInterface $platform
     */
    public function __construct(ColumnInterface $column, PlatformInterface $platform)
    {
        $this->column = $column;
        $this->platform = $platform;
    }

    /**
     * Get the doctrine column
     *
     * @return Column
     * @throws \Doctrine\DBAL\DBALException
     */
    public function toDoctrine()
    {
        $column = new Column(
            $this->column->name(),
            Type::getType(
                $this->column->type()->declaration($this->column)
            ),
            $this->columnOptions()
        );

        $column->setCustomSchemaOptions($this->column->options());

        return $column;
    }

    /**
     * @return array
     */
    private function columnOptions()
    {
        return [
            'notnull'       => !$this->column->nillable(),
            'length'        => $this->column->length(),
            'autoincrement' => $this->column->autoIncrement(),
            'unsigned'      => $this->column->unsigned(),
            'fixed'         => $this->column->fixed(),
            'comment'       => $this->column->comment(),
            'precision'     => $this->column->precision(),
            'scale'         => $this->column->scale(),
            'default'       => $this->column->defaultValue(),
        ];
    }
}
