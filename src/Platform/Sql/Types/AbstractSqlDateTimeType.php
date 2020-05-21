<?php

namespace Bdf\Prime\Platform\Sql\Types;

use Bdf\Prime\Platform\AbstractPlatformType;
use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Types\Helpers\DateTimeHelper;

/**
 * Default behavior for sql date time type
 */
abstract class AbstractSqlDateTimeType extends AbstractPlatformType
{
    use DateTimeHelper;

    /**
     * {@inheritdoc}
     */
    public function declaration(ColumnInterface $column)
    {
        return $this->name;
    }
}
