<?php

namespace Bdf\Prime\Types;

/**
 * Accept a date type (Y-m-d)
 */
class DateType extends AbstractDateTimeType
{
    /**
     * DateType constructor.
     *
     * @param string $name
     * @param string $className
     * @param \DateTimeZone|null $timezone
     */
    public function __construct($name = self::DATE, string $className = \DateTime::class, ?\DateTimeZone $timezone = null)
    {
        parent::__construct($name);

        $this->format = 'Y-m-d';
        $this->resetFields = true;
        $this->className = $className;
        $this->timezone = $timezone;
    }
}
