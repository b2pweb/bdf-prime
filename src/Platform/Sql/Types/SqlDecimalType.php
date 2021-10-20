<?php

namespace Bdf\Prime\Platform\Sql\Types;

use Bdf\Prime\Platform\AbstractPlatformType;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Types\PhpTypeInterface;
use Doctrine\DBAL\Types\Types;

/**
 * Type that maps an SQL DECIMAL to a PHP string.
 */
class SqlDecimalType extends AbstractPlatformType
{
    /**
     * {@inheritdoc}
     */
    public function __construct(PlatformInterface $platform, $name = self::DECIMAL)
    {
        parent::__construct($platform, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function declaration(ColumnInterface $column)
    {
        return Types::DECIMAL;
    }

    /**
     * {@inheritdoc}
     */
    public function phpType(): string
    {
        return PhpTypeInterface::STRING;
    }
}
