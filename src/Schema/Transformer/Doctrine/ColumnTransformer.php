<?php

namespace Bdf\Prime\Schema\Transformer\Doctrine;

use Bdf\Prime\Schema\ColumnInterface;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;

/**
 * Transform prime column to doctrine column
 */
final readonly class ColumnTransformer
{
    public function __construct(
        private ColumnInterface $column,
    ) {
    }

    /**
     * Get the doctrine column
     */
    public function toDoctrine(): Column
    {
        $column = Column::editor()
            ->setUnquotedName($this->column->name())
            ->setType(Type::getType($this->column->type()->declaration($this->column)))
            ->setNotNull(!$this->column->nillable())
            ->setLength($this->column->length())
            ->setAutoincrement($this->column->autoIncrement())
            ->setUnsigned($this->column->unsigned())
            ->setFixed($this->column->fixed())
            ->setComment($this->column->comment() ?? '')
            ->setPrecision($this->column->precision())
            ->setScale($this->column->scale() ?? 0)
            ->setDefaultValue($this->column->defaultValue())
            ->create();

        $column->setPlatformOptions($this->column->options());

        return $column;
    }
}
