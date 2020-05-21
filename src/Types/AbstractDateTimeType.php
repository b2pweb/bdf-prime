<?php

namespace Bdf\Prime\Types;

use Bdf\Prime\Types\Helpers\DateTimeHelper;

/**
 * Default behavior for date time type
 */
abstract class AbstractDateTimeType extends AbstractFacadeType
{
    use DateTimeHelper;

    /**
     * {@inheritdoc}
     */
    protected function defaultType()
    {
        return self::STRING;
    }
}
