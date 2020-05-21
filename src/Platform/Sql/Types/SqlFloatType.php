<?php

namespace Bdf\Prime\Platform\Sql\Types;

use Bdf\Prime\Platform\AbstractPlatformType;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Types\PhpTypeInterface;
use Doctrine\DBAL\Types\Type;

/**
 * Basic float type for database
 */
class SqlFloatType extends AbstractPlatformType
{
    /**
     * {@inheritdoc}
     */
    public function __construct(PlatformInterface $platform, $name = self::FLOAT)
    {
        parent::__construct($platform, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value, array $fieldOptions = [])
    {
        return $value === null ? null : (double) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function declaration(ColumnInterface $column)
    {
        return Type::FLOAT;
    }

    /**
     * {@inheritdoc}
     */
    public function phpType()
    {
        return PhpTypeInterface::DOUBLE;
    }
}
