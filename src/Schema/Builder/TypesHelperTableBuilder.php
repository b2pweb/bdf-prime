<?php

namespace Bdf\Prime\Schema\Builder;

use Bdf\Prime\Platform\PlatformTypeInterface;
use Bdf\Prime\Platform\PlatformTypesInterface;
use Bdf\Prime\Schema\IndexInterface;
use Bdf\Prime\Schema\TableInterface;
use Bdf\Prime\Types\TypeInterface;
use Bdf\Prime\Types\TypesHelperInterface;

/**
 * Decorate TableBuilder for adding extra helper methods for adding types
 * Not like table builder, all helpers method will return $this
 * All those methods can be chained.
 *
 * The given type registry must be a @see PlatformTypesInterface for resolve "complex" types like bigint, or array
 *
 * <code>
 * $builder
 *     ->bigint('id')->autoincrement()->primary()
 *     ->string('first_name', 32)->nillable()->unique('idx_name')
 *     ->string('last_name', 32)->nillable()->unique('idx_name')
 * ;
 * </code>
 */
final class TypesHelperTableBuilder implements TableBuilderInterface, TypesHelperInterface
{
    /**
     * @var TableBuilderInterface
     */
    private $builder;

    /**
     * @var PlatformTypesInterface
     */
    private $types;


    /**
     * TypesHelperTableBuilder constructor.
     *
     * @param TableBuilderInterface $builder
     * @param PlatformTypesInterface $types
     */
    public function __construct(TableBuilderInterface $builder, PlatformTypesInterface $types)
    {
        $this->builder = $builder;
        $this->types = $types;
    }

    //===================//
    // Delegated methods //
    //===================//

    /**
     * {@inheritdoc}
     */
    public function name(string $name)
    {
        $this->builder->name($name);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function options(array $options)
    {
        $this->builder->options($options);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function indexes(array $indexes)
    {
        $this->builder->indexes($indexes);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function primary($columns = null, ?string $name = null)
    {
        $this->builder->primary($columns, $name);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function add(string $column, PlatformTypeInterface $type, array $options = [])
    {
        return $this->builder->add($column, $type, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function column(?string $name = null)
    {
        return $this->builder->column($name);
    }

    /**
     * {@inheritdoc}
     */
    public function foreignKey($foreignTable, array $localColumnNames, array $foreignColumnNames, array $options = [], ?string $constraintName = null)
    {
        $this->builder->foreignKey($foreignTable, $localColumnNames, $foreignColumnNames, $options, $constraintName);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function index($columns, int $type = IndexInterface::TYPE_SIMPLE, $name = null, array $options = [])
    {
        $this->builder->index($columns, $type, $name, $options);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function build(): TableInterface
    {
        return $this->builder->build();
    }

    //================//
    // Helper methods //
    //================//

    /**
     * Add a new column with type as string
     *
     * @param string $column
     * @param string $type
     * @param array $options
     *
     * @return ColumnBuilderInterface
     *
     * @see TableBuilderInterface::add()
     */
    public function addTypeAsString(string $column, string $type, array $options = [])
    {
        return $this->add(
            $column,
            $this->types->native($type),
            $options
        );
    }

    /**
     * {@inheritdoc}
     */
    public function string(string $name, int $length = 255, ?string $default = null)
    {
        $this->addTypeAsString($name, TypeInterface::STRING)
            ->length($length)
            ->setDefault($default)
        ;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function text(string $name, ?string $default = null)
    {
        $this->addTypeAsString($name, TypeInterface::TEXT)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function integer(string $name, ?int $default = null)
    {
        $this->addTypeAsString($name, TypeInterface::INTEGER)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function tinyint(string $name, ?int $default = null)
    {
        $this->addTypeAsString($name, TypeInterface::TINYINT)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function smallint(string $name, ?int $default = null)
    {
        $this->addTypeAsString($name, TypeInterface::SMALLINT)->setDefault($default);

        return $this;
    }

    /**
     * Create a new medium integer (3-byte) column on the table.
     *
     * @param  string  $name
     * @param  int|null   $default
     *
     * @return $this
     */
    public function mediumint(string $name, ?int $default = null)
    {
        $this->addTypeAsString($name, 'mediumint')->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function bigint(string $name, $default = null)
    {
        $this->addTypeAsString($name, TypeInterface::BIGINT)->setDefault($default);

        return $this;
    }

    /**
     * Create a new unsigned tiny integer (1-byte) column on the table.
     *
     * @param string $name
     * @param int|null $default
     *
     * @return $this
     */
    public function unsignedTinyint(string $name, ?int $default = null)
    {
        return $this->tinyint($name, $default)->unsigned();
    }

    /**
     * Create a new unsigned small integer (2-byte) column on the table.
     *
     * @param string $name
     * @param int|null $default
     *
     * @return $this
     */
    public function unsignedSmallint(string $name, ?int $default = null)
    {
        return $this->smallint($name, $default)->unsigned();
    }

    /**
     * Create a new unsigned medium integer (3-byte) column on the table.
     *
     * @param string $name
     * @param int|null $default
     *
     * @return $this
     */
    public function unsignedMediumint(string $name, ?int $default = null)
    {
        return $this->mediumint($name, $default)->unsigned();
    }

    /**
     * Create a new unsigned integer (4-byte) column on the table.
     *
     * @param string $name
     * @param int|null $default
     *
     * @return $this
     */
    public function unsignedInteger(string $name, ?int $default = null)
    {
        return $this->integer($name, $default)->unsigned();
    }

    /**
     * Create a new unsigned big integer (8-byte) column on the table.
     *
     * @param string $name
     * @param mixed $default
     *
     * @return $this
     */
    public function unsignedBigint(string $name, $default = null)
    {
        return $this->bigint($name, $default)->unsigned();
    }

    /**
     * {@inheritdoc}
     */
    public function float(string $name, ?float $default = null)
    {
        $this->addTypeAsString($name, TypeInterface::FLOAT)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function double(string $name, ?float $default = null)
    {
        $this->addTypeAsString($name, TypeInterface::DOUBLE)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function decimal(string $name, $default = null)
    {
        $this->addTypeAsString($name, TypeInterface::DECIMAL)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function boolean(string $name, ?bool $default = null)
    {
        $this->addTypeAsString($name, TypeInterface::BOOLEAN)->setDefault($default);

        return $this;
    }

    /**
     * Create a new enum column on the table.
     *
     * @param  string  $name
     * @param  array   $allowed
     *
     * @return $this
     */
    //public function enum($name, array $allowed)
    //{
    //    $this->addTypeAsString($name, 'enum', ['allowed' => $allowed]);
    //
    //    return $this;
    //}

    /**
     * {@inheritdoc}
     */
    public function date(string $name, $default = null)
    {
        $this->addTypeAsString($name, TypeInterface::DATE)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function dateTime(string $name, $default = null)
    {
        $this->addTypeAsString($name, TypeInterface::DATETIME)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function dateTimeTz(string $name, $default = null)
    {
        $this->addTypeAsString($name, TypeInterface::DATETIMETZ)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function time(string $name, $default = null)
    {
        $this->addTypeAsString($name, TypeInterface::TIME)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function timestamp(string $name, $default = null)
    {
        $this->addTypeAsString($name, TypeInterface::TIMESTAMP)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function binary(string $name, ?string $default = null)
    {
        $this->addTypeAsString($name, TypeInterface::BINARY)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function blob(string $name, ?string $default = null)
    {
        $this->addTypeAsString($name, TypeInterface::BLOB)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function guid(string $name, $default = null)
    {
        $this->addTypeAsString($name, TypeInterface::GUID)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function json(string $name, $default = null)
    {
        $this->addTypeAsString($name, TypeInterface::JSON)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function simpleArray(string $name, ?array $default = null)
    {
        $this->addTypeAsString($name, TypeInterface::TARRAY)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function object(string $name, $default = null)
    {
        $this->addTypeAsString($name, TypeInterface::OBJECT)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function arrayObject(string $name, ?array $default = null)
    {
        $this->addTypeAsString($name, TypeInterface::ARRAY_OBJECT)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function searchableArray(string $name, ?array $default = null)
    {
        $this->addTypeAsString($name, TypeInterface::TARRAY)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function arrayOf(string $name, string $type, ?array $default = null)
    {
        $this->addTypeAsString($name, $type.'[]')->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function arrayOfInt(string $name, ?array $default = null)
    {
        return $this->arrayOf($name, TypeInterface::INTEGER, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function arrayOfDouble(string $name, ?array $default = null)
    {
        return $this->arrayOf($name, TypeInterface::DOUBLE, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function arrayOfDateTime(string $name, ?array $default = null)
    {
        return $this->arrayOf($name, TypeInterface::DATETIME, $default);
    }

    /**
     * Specify the autoincrement key.
     * /!\ Unlike FieldBuilder, this method will not set the column as primary key
     *
     * @param bool $flag         Activate/Deactivate autoincrement
     *
     * @return $this
     *
     * @see ColumnBuilderInterface::autoincrement()
     */
    public function autoincrement(bool $flag = true)
    {
        $this->column()->autoincrement($flag);

        return $this;
    }

    /**
     * Set nillable flag of current field
     *
     * @param bool $flag         Activate/Deactivate nillable
     *
     * @return $this             This builder instance
     */
    public function nillable(bool $flag = true)
    {
        $this->column()->nillable($flag);

        return $this;
    }

    /**
     * Set unsigned flag on the current field
     *
     * @param bool $flag Activate/Deactivate unsigned
     *
     * @return $this This builder instance
     */
    public function unsigned(bool $flag = true)
    {
        $this->column()->unsigned($flag);

        return $this;
    }

    /**
     * Define the decimal field precision
     *
     * @param int $precision The number of significant digits that are stored for values
     * @param int $scale The number of digits that can be stored following the decimal point
     *
     * @return $this This builder instance
     */
    public function precision(int $precision, int $scale)
    {
        $this->column()->precision($precision, $scale);

        return $this;
    }
}
