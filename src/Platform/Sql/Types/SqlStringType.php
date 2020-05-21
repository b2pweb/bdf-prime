<?php

namespace Bdf\Prime\Platform\Sql\Types;

use Bdf\Prime\Platform\AbstractPlatformType;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Types\PhpTypeInterface;
use Doctrine\DBAL\Types\Type;

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
        self::STRING => Type::STRING,
        self::TEXT   => Type::TEXT,
        self::BIGINT => Type::BIGINT,
        self::BINARY => Type::BINARY,
        self::BLOB   => Type::BLOB,
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
        return isset(self::$doctrineTypeMap[$this->name]) ? self::$doctrineTypeMap[$this->name] : Type::TEXT;
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
