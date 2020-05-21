<?php

namespace Bdf\Prime\Platform\Sql\Types;

use Bdf\Prime\Platform\AbstractPlatformType;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Types\PhpTypeInterface;
use Doctrine\DBAL\Types\Type;

/**
 * Basic boolean type for database
 */
class SqlBooleanType extends AbstractPlatformType
{
    /**
     * {@inheritdoc}
     */
    public function __construct(PlatformInterface $platform, $name = self::BOOLEAN)
    {
        parent::__construct($platform, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value, array $fieldOptions = [])
    {
        return $value === null ? null : $this->platform->grammar()->convertFromBoolean($value);
    }

    /**
     * {@inheritdoc}
     */
    public function toDatabase($value)
    {
        return $value === null ? null : $this->platform->grammar()->convertBooleans($value);
    }

    /**
     * {@inheritdoc}
     */
    public function declaration(ColumnInterface $column)
    {
        return Type::BOOLEAN;
    }

    /**
     * {@inheritdoc}
     */
    public function phpType()
    {
        return PhpTypeInterface::BOOLEAN;
    }
}
