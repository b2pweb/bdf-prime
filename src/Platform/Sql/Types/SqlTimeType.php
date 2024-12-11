<?php

namespace Bdf\Prime\Platform\Sql\Types;

use Bdf\Prime\Platform\PlatformInterface;

/**
 * Accept a time type (H:i:s)
 */
class SqlTimeType extends AbstractSqlDateTimeType
{
    /**
     * {@inheritdoc}
     */
    public function __construct(PlatformInterface $platform, $name = self::TIME, string $className = \DateTime::class, ?\DateTimeZone $timezone = null)
    {
        parent::__construct($platform, $name);

        $this->format = $platform->grammar()->getTimeFormatString();
        $this->resetFields = true;
        $this->className = $className;
        $this->timezone = $timezone;
    }
}
