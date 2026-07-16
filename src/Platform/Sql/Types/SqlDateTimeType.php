<?php

namespace Bdf\Prime\Platform\Sql\Types;

use Bdf\Prime\Platform\PlatformInterface;
use DateTimeInterface;

/**
 * Accept a datetime type
 */
final class SqlDateTimeType extends AbstractSqlDateTimeType
{
    /**
     * SqlDateTimeType constructor.
     *
     * @param PlatformInterface $platform
     * @param string $name
     * @param string $className
     * @param \DateTimeZone|null $timezone
     */
    public function __construct(
        PlatformInterface $platform,
        string $name = self::DATETIME,
        string $className = \DateTime::class,
        ?\DateTimeZone $timezone = null
    ) {
        parent::__construct($platform, $name);

        $this->format = $platform->grammar()->getDateTimeFormatString();
        $this->className = $className;
        $this->timezone = $timezone;
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase(mixed $value, array $fieldOptions = []): ?DateTimeInterface
    {
        // Handle non-nillable date time
        if ($value === '0000-00-00 00:00:00') {
            return null;
        }

        return parent::fromDatabase($value, $fieldOptions);
    }
}
