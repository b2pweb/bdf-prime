<?php

namespace Bdf\Prime\Types;

use Bdf\Prime\Types\Helpers\DateTimeHelper;
use DateTime;
use DateTimeInterface;

/**
 * Type that maps a SQL TIMESTAMP to a PHP DateTime Object
 */
final class TimestampType extends AbstractFacadeType
{
    use DateTimeHelper;

    /**
     * TimestampType constructor.
     *
     * @param string $name
     * @param string $className
     */
    public function __construct(string $name = self::TIMESTAMP, string $className = DateTime::class)
    {
        parent::__construct($name);

        $this->format = 'U';
        $this->className = $className;
    }

    /**
     * {@inheritdoc}
     */
    public function toDatabase(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        // If the date is formatted, it can be considered as a simple string
        if (!$value instanceof DateTimeInterface) {
            return $value;
        }

        return $value->getTimestamp();
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultType(): string
    {
        return self::INTEGER;
    }
}
