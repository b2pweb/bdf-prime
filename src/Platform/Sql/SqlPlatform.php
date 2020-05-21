<?php

namespace Bdf\Prime\Platform\Sql;

use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\PlatformTypes;
use Bdf\Prime\Platform\Sql\Types\SqlBooleanType;
use Bdf\Prime\Platform\Sql\Types\SqlDateTimeType;
use Bdf\Prime\Platform\Sql\Types\SqlDateTimeTzType;
use Bdf\Prime\Platform\Sql\Types\SqlDateType;
use Bdf\Prime\Platform\Sql\Types\SqlDecimalType;
use Bdf\Prime\Platform\Sql\Types\SqlFloatType;
use Bdf\Prime\Platform\Sql\Types\SqlGuidType;
use Bdf\Prime\Platform\Sql\Types\SqlIntegerType;
use Bdf\Prime\Platform\Sql\Types\SqlStringType;
use Bdf\Prime\Platform\Sql\Types\SqlTimeType;
use Bdf\Prime\Types\TypeInterface;
use Bdf\Prime\Types\TypesRegistryInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Base class for SQL platforms
 */
class SqlPlatform implements PlatformInterface
{
    /**
     * @var AbstractPlatform
     */
    private $grammar;

    /**
     * @var TypesRegistryInterface
     */
    private $types;


    /**
     * SqlPlatform constructor.
     *
     * @param AbstractPlatform $grammar
     * @param TypesRegistryInterface $types
     */
    public function __construct(AbstractPlatform $grammar, TypesRegistryInterface $types)
    {
        $this->grammar = $grammar;
        $this->types = new PlatformTypes(
            $this,
            [
                TypeInterface::DATETIME => SqlDateTimeType::class,
                TypeInterface::DATETIMETZ => SqlDateTimeTzType::class,
                TypeInterface::DATE => SqlDateType::class,
                TypeInterface::TIME => SqlTimeType::class,
                TypeInterface::DECIMAL => SqlDecimalType::class,
                TypeInterface::DOUBLE => SqlFloatType::class,
                TypeInterface::FLOAT => SqlFloatType::class,
                TypeInterface::BOOLEAN => SqlBooleanType::class,
                TypeInterface::GUID => SqlGuidType::class,
            ],
            $types
        );

        foreach (SqlStringType::typeNames() as $name) {
            $this->types->register(SqlStringType::class, $name);
        }

        foreach (SqlIntegerType::typeNames() as $name) {
            $this->types->register(SqlIntegerType::class, $name);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return $this->grammar->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function types()
    {
        return $this->types;
    }

    /**
     * {@inheritdoc}
     */
    public function grammar()
    {
        return $this->grammar;
    }
}
