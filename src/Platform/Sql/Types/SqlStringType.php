<?php

namespace Bdf\Prime\Platform\Sql\Types;

use Bdf\Prime\Platform\AbstractPlatformType;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Types\PhpTypeInterface;
use Doctrine\DBAL\Types\Types;

/**
 * Basic string type for database
 *
 * @todo Handle "static" CHAR type
 */
class SqlStringType extends AbstractPlatformType
{
    /**
     * @var string[]
     */
    private static $doctrineTypeMap = [
        self::STRING => Types::STRING,
        self::TEXT   => Types::TEXT,
        self::BIGINT => Types::BIGINT,
        self::BINARY => Types::BINARY,
        self::BLOB   => Types::BLOB,
    ];

    /**
     * {@inheritdoc}
     */
    public function __construct(PlatformInterface $platform, $name = self::STRING)
    {
        parent::__construct($platform, $name);
    }

    /**
     * {@inheritdoc}
     *
     * @todo can we remove this transformation for string value ?
     */
    public function toDatabase($value)
    {
        return $value === null ? null : (string) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value, array $fieldOptions = [])
    {
        return $value === null ? null : (string) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function declaration(ColumnInterface $column)
    {
        return isset(self::$doctrineTypeMap[$this->name]) ? self::$doctrineTypeMap[$this->name] : Types::TEXT;
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
        return PhpTypeInterface::STRING;
    }
}
