<?php

namespace Bdf\Prime\Types;

use Bdf\Prime\Types\Helpers\DateTimeHelper;

/**
 * Type that maps a SQL TIMESTAMP to a PHP DateTime Object
 */
class TimestampType extends AbstractFacadeType
{
    use DateTimeHelper;

    /**
     * TimestampType constructor.
     *
     * @param string $name
     * @param string $className
     */
    public function __construct($name = self::TIMESTAMP, string $className = \DateTime::class)
    {
        parent::__construct($name);

        $this->format = 'U';
        $this->className = $className;
    }

    /**
     * {@inheritdoc}
     *
     * @param string|\DateTimeInterface $value
     */
    public function toDatabase($value)
    {
        if ($value === null) {
            return null;
        }

        // If the date is formatted, it can be considered as a simple string
        if (!$value instanceof \DateTimeInterface) {
            return $value;
        }

        return $value->getTimestamp();
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultType()
    {
        return self::INTEGER;
    }
}
