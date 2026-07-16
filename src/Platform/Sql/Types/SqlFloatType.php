<?php

namespace Bdf\Prime\Platform\Sql\Types;

use Bdf\Prime\Platform\AbstractPlatformType;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Schema\ColumnInterface;
use Bdf\Prime\Types\PhpTypeInterface;
use Doctrine\DBAL\Types\Types;

/**
 * Basic float type for database
 */
final class SqlFloatType extends AbstractPlatformType
{
    /**
     * {@inheritdoc}
     */
    public function __construct(PlatformInterface $platform, string $name = self::FLOAT)
    {
        parent::__construct($platform, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase(mixed $value, array $fieldOptions = []): ?float
    {
        return $value === null ? null : (float) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function declaration(ColumnInterface $column): string
    {
        return Types::FLOAT;
    }

    /**
     * {@inheritdoc}
     */
    public function phpType(): string
    {
        return PhpTypeInterface::DOUBLE;
    }
}
