<?php

namespace Bdf\Prime\Schema\Builder;

use Bdf\Prime\Platform\PlatformTypeInterface;
use Bdf\Prime\Platform\PlatformTypesInterface;
use Bdf\Prime\Schema\IndexInterface;
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
    public function name($name)
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
    public function primary($columns = null, $name = null)
    {
        $this->builder->primary($columns, $name);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function add($column, PlatformTypeInterface $type, array $options = [])
    {
        return $this->builder->add($column, $type, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function column($name = null)
    {
        return $this->builder->column($name);
    }

    /**
     * {@inheritdoc}
     */
    public function foreignKey($foreignTable, array $localColumnNames, array $foreignColumnNames, array $options = [], $constraintName = null)
    {
        $this->builder->foreignKey($foreignTable, $localColumnNames, $foreignColumnNames, $options, $constraintName);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function index($columns, $type = IndexInterface::TYPE_SIMPLE, $name = null, array $options = [])
    {
        $this->builder->index($columns, $type, $name, $options);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function build()
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
    public function addTypeAsString($column, $type, array $options = [])
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
    public function string($column, $length = 255, $default = null)
    {
        $this->addTypeAsString($column, TypeInterface::STRING)
            ->length($length)
            ->setDefault($default)
        ;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function text($column, $default = null)
    {
        $this->addTypeAsString($column, TypeInterface::TEXT)->setDefault($default);

        return $this;
    }

    /**
     * Create a new integer (4-byte) column on the table.
     *
     * @param  string $column
     * @param  bool|int $autoIncrement
     * @param  bool $unsigned
     * @param  mixed   $default
     *
     * @return $this
     */
    public function integer($column, $autoIncrement = false, $unsigned = false, $default = null)
    {
        if ($unsigned === false && $default === null && is_int($autoIncrement)) {
            $default = $autoIncrement;
            $autoIncrement = false;
        }

        $this->addTypeAsString($column, TypeInterface::INTEGER)
            ->autoincrement($autoIncrement)
            ->unsigned($unsigned)
            ->setDefault($default)
        ;

        return $this;
    }

    /**
     * Create a new tiny integer (1-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool|int  $autoIncrement
     * @param  bool  $unsigned
     * @param  mixed   $default
     *
     * @return $this
     */
    public function tinyint($column, $autoIncrement = false, $unsigned = false, $default = null)
    {
        if ($unsigned === false && $default === null && is_int($autoIncrement)) {
            $default = $autoIncrement;
            $autoIncrement = false;
        }

        $this->addTypeAsString($column, TypeInterface::TINYINT)
            ->autoincrement($autoIncrement)
            ->unsigned($unsigned)
            ->setDefault($default)
        ;

        return $this;
    }

    /**
     * Create a new small integer (2-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool|int  $autoIncrement
     * @param  bool  $unsigned
     * @param  mixed   $default
     *
     * @return $this
     */
    public function smallint($column, $autoIncrement = false, $unsigned = false, $default = null)
    {
        if ($unsigned === false && $default === null && is_int($autoIncrement)) {
            $default = $autoIncrement;
            $autoIncrement = false;
        }

        $this->addTypeAsString($column, TypeInterface::SMALLINT)
            ->autoincrement($autoIncrement)
            ->unsigned($unsigned)
            ->setDefault($default)
        ;

        return $this;
    }

    /**
     * Create a new medium integer (3-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool|int  $autoIncrement
     * @param  bool  $unsigned
     * @param  mixed   $default
     *
     * @return $this
     */
    public function mediumint($column, $autoIncrement = false, $unsigned = false, $default = null)
    {
        if ($unsigned === false && $default === null && is_int($autoIncrement)) {
            $default = $autoIncrement;
            $autoIncrement = false;
        }

        $this->addTypeAsString($column, 'mediumint')
            ->autoincrement($autoIncrement)
            ->unsigned($unsigned)
            ->setDefault($default)
        ;

        return $this;
    }

    /**
     * Create a new big integer (8-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool|int  $autoIncrement
     * @param  bool  $unsigned
     * @param  mixed   $default
     *
     * @return $this
     */
    public function bigint($column, $autoIncrement = false, $unsigned = false, $default = null)
    {
        if ($unsigned === false && $default === null && is_int($autoIncrement)) {
            $default = $autoIncrement;
            $autoIncrement = false;
        }

        $this->addTypeAsString($column, TypeInterface::BIGINT)
            ->autoincrement($autoIncrement)
            ->unsigned($unsigned)
            ->setDefault($default)
        ;

        return $this;
    }

    /**
     * Create a new unsigned tiny integer (1-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     *
     * @return $this
     */
    public function unsignedTinyint($column, $autoIncrement = false)
    {
        return $this->tinyint($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned small integer (2-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     *
     * @return $this
     */
    public function unsignedSmallint($column, $autoIncrement = false)
    {
        return $this->smallint($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned medium integer (3-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     *
     * @return $this
     */
    public function unsignedMediumint($column, $autoIncrement = false)
    {
        return $this->mediumint($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned integer (4-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     *
     * @return $this
     */
    public function unsignedInteger($column, $autoIncrement = false)
    {
        return $this->integer($column, $autoIncrement, true);
    }

    /**
     * Create a new unsigned big integer (8-byte) column on the table.
     *
     * @param  string  $column
     * @param  bool  $autoIncrement
     *
     * @return $this
     */
    public function unsignedBigint($column, $autoIncrement = false)
    {
        return $this->bigint($column, $autoIncrement, true);
    }

    /**
     * Create a new float column on the table.
     *
     * @param  string  $column
     * @param  int     $precision
     * @param  int     $scale
     * @param  mixed   $default
     *
     * @return $this
     */
    public function float($column, $precision = null, $scale = null, $default = null)
    {
        if ($scale === null && $default === null && is_float($precision)) {
            $default = $precision;
            $precision = null;
        }

        $this->addTypeAsString($column, TypeInterface::FLOAT)
            ->precision($precision, $scale)
            ->setDefault($default)
        ;

        return $this;
    }

    /**
     * Create a new double column on the table.
     *
     * @param  string  $column
     * @param  int     $precision
     * @param  int     $scale
     * @param  mixed   $default
     *
     * @return $this
     */
    public function double($column, $precision = null, $scale = null, $default = null)
    {
        if ($scale === null && $default === null && is_float($precision)) {
            $default = $precision;
            $precision = null;
        }

        $this->addTypeAsString($column, TypeInterface::DOUBLE)
            ->precision($precision, $scale)
            ->setDefault($default)
        ;

        return $this;
    }

    /**
     * Create a new decimal column on the table.
     *
     * @param  string  $column
     * @param  int     $precision
     * @param  int     $scale
     * @param  mixed   $default
     *
     * @return $this
     */
    public function decimal($column, $precision = null, $scale = null, $default = null)
    {
        if ($scale === null && $default === null && is_float($precision)) {
            $default = $precision;
            $precision = null;
        }

        $this->addTypeAsString($column, TypeInterface::DECIMAL)
            ->precision($precision, $scale)
            ->setDefault($default)
        ;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function boolean($column, $default = null)
    {
        $this->addTypeAsString($column, TypeInterface::BOOLEAN)->setDefault($default);

        return $this;
    }

    /**
     * Create a new enum column on the table.
     *
     * @param  string  $column
     * @param  array   $allowed
     *
     * @return $this
     */
//    public function enum($column, array $allowed)
//    {
//        $this->addTypeAsString($column, 'enum', ['allowed' => $allowed]);
//
//        return $this;
//    }

    /**
     * {@inheritdoc}
     */
    public function date($column, $default = null)
    {
        $this->addTypeAsString($column, TypeInterface::DATE)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function dateTime($column, $default = null)
    {
        $this->addTypeAsString($column, TypeInterface::DATETIME)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function dateTimeTz($column, $default = null)
    {
        $this->addTypeAsString($column, TypeInterface::DATETIMETZ)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function time($column, $default = null)
    {
        $this->addTypeAsString($column, TypeInterface::TIME)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function timestamp($column, $default = null)
    {
        $this->addTypeAsString($column, TypeInterface::TIMESTAMP)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function binary($column, $default = null)
    {
        $this->addTypeAsString($column, TypeInterface::BINARY)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function blob($column, $default = null)
    {
        $this->addTypeAsString($column, TypeInterface::BLOB)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function guid($column, $default = null)
    {
        $this->addTypeAsString($column, TypeInterface::GUID)->setDefault($default);

        return $this;
    }

    /**
     * Create a new json column on the table.
     *
     * @param  string  $column
     * @param  mixed   $default
     *
     * @return $this
     */
    public function json($column, $default = null)
    {
        $this->addTypeAsString($column, TypeInterface::JSON)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function simpleArray($column, $default = null)
    {
        $this->addTypeAsString($column, TypeInterface::TARRAY)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function object($column, $default = null)
    {
        $this->addTypeAsString($column, TypeInterface::OBJECT)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function arrayObject($column, $default = null)
    {
        $this->addTypeAsString($column, TypeInterface::ARRAY_OBJECT)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function searchableArray($column, $default = null)
    {
        $this->addTypeAsString($column, TypeInterface::TARRAY)->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function arrayOf($column, $type, $default = null)
    {
        $this->addTypeAsString($column, $type.'[]')->setDefault($default);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function arrayOfInt($name, $default = null)
    {
        return $this->arrayOf($name, TypeInterface::INTEGER, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function arrayOfDouble($name, $default = null)
    {
        return $this->arrayOf($name, TypeInterface::DOUBLE, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function arrayOfDateTime($name, $default = null)
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
    public function autoincrement($flag = true)
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
    public function nillable($flag = true)
    {
        $this->column()->nillable($flag);

        return $this;
    }
}
