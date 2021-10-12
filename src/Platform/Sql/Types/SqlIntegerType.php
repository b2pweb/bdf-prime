<?php

namespace Bdf\Prime\Platform\Sql\Types;

use Bdf\Prime\Platform\AbstractPlatformType;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Types\PhpTypeInterface;
use Doctrine\DBAL\Types\Types;

/**
 * Basic integer type for database
 */
class SqlIntegerType extends AbstractPlatformType
{
    /**
     * @var string[]
     */
    private static $doctrineTypeMap = [
        self::INTEGER  => Types::INTEGER,
        self::SMALLINT => Types::SMALLINT,
        self::TINYINT  => Types::SMALLINT,
    ];


    /**
     * {@inheritdoc}
     */
    public function __construct(PlatformInterface $platform, $name = self::INTEGER)
    {
        parent::__construct($platform, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value, array $fieldOptions = [])
    {
        return $value === null ? null : (int) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function declaration(ColumnInterface $column)
    {
        return isset(self::$doctrineTypeMap[$this->name]) ? self::$doctrineTypeMap[$this->name] : Types::INTEGER;
    }

    /**
     * Get the handled types names
     *
     * @return string[]
     */
    public static function typeNames()
    {
        return array_keys(self::$doctrineTypeMap);
    }

    /**
     * {@inheritdoc}
     */
    public function phpType()
    {
        return PhpTypeInterface::INTEGER;
    }
}
