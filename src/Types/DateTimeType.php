<?php

namespace Bdf\Prime\Types;

/**
 * Type that maps a DATETIME ISO8601 to a PHP DateTime Object
 */
class DateTimeType extends AbstractDateTimeType
{
    /**
     * DateTimeType constructor.
     *
     * @param string $type
     * @param string $format
     * @param string $className
     * @param \DateTimeZone|null $timezone
     */
    public function __construct(
        string $type = self::DATETIME,
        string $format = \DateTime::ATOM,
        string $className = \DateTime::class,
        \DateTimeZone $timezone = null
    ) {
        parent::__construct($type);

        $this->format = $format;
        $this->className = $className;
        $this->timezone = $timezone;
    }
}
