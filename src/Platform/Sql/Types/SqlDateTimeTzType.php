<?php

namespace Bdf\Prime\Platform\Sql\Types;

use Bdf\Prime\Platform\PlatformInterface;

/**
 * Accept a datetime type with timezone
 */
class SqlDateTimeTzType extends AbstractSqlDateTimeType
{
    /**
     * SqlDateTimeTzType constructor.
     *
     * @param PlatformInterface $platform
     * @param string $name
     * @param string $className
     */
    public function __construct(PlatformInterface $platform, $name = self::DATETIMETZ, string $className = \DateTime::class)
    {
        parent::__construct($platform, $name);

        $this->format = $platform->grammar()->getDateTimeTzFormatString();
        $this->className = $className;
    }
}
