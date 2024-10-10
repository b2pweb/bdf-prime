<?php

namespace Bdf\Prime\Types;

/**
 * Accept a time type (H:i:s)
 */
class TimeType extends AbstractDateTimeType
{
    /**
     * TimeType constructor.
     *
     * @param string $name
     * @param string $className
     * @param \DateTimeZone|null $timezone
     */
    public function __construct($name = self::TIME, string $className = \DateTime::class, ?\DateTimeZone $timezone = null)
    {
        parent::__construct($name);

        $this->format = 'H:i:s';
        $this->resetFields = true;
        $this->className = $className;
        $this->timezone = $timezone;
    }
}
