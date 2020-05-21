<?php

namespace Bdf\Prime\Platform\Sql\Types;

use Bdf\Prime\Platform\PlatformInterface;

/**
 * Accept a date type (Y-m-d)
 */
class SqlDateType extends AbstractSqlDateTimeType
{
    /**
     * {@inheritdoc}
     */
    public function __construct(PlatformInterface $platform, $name = self::DATE, string $className = \DateTime::class, \DateTimeZone $timezone = null)
    {
        parent::__construct($platform, $name);

        $this->format = $platform->grammar()->getDateFormatString();
        $this->resetFields = true;
        $this->className = $className;
        $this->timezone = $timezone;
    }
}
