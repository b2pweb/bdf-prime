<?php

namespace Bdf\Prime\Mapper\Builder;

use ArrayAccess;
use ArrayIterator;
use BackedEnum;
use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Platform\Sql\Types\SqlJsonType;
use Bdf\Prime\Types\BackedEnumType;
use Bdf\Prime\Types\TypeInterface;
use Bdf\Prime\Types\TypesHelperInterface;
use Bdf\Prime\Types\UnitEnumType;
use Closure;
use IteratorAggregate;
use UnitEnum;

/**
 * FieldBuilder
 *
 * @psalm-type FieldDefinition = array{
 *     type?: string,
 *     default?: mixed,
 *     primary?: "autoincrement"|"sequence"|true,
 *     class?: class-string,
 *     embedded?: array<string, array>,
 *     class_map?: class-string[],
 *     polymorph?: bool,
 *     discriminator_field?: string,
 *     discriminator_attribute?: string,
 *     length?: int|null,
 *     comment?: string|null,
 *     alias?: string,
 *     precision?: int,
 *     scale?: int,
 *     nillable?: bool,
 *     unsigned?: bool,
 *     unique?: bool|string,
 *     fixed?: bool,
 *     phpOptions?: array,
 *     customSchemaOptions?: array,
 *     platformOptions?: array,
 *     columnDefinition?: string,
 *     storageType?: string,
 *     virtual?: bool
 * }
 *
 * @implements IteratorAggregate<string, FieldDefinition>
 * @implements ArrayAccess<string, FieldDefinition>
 */
class FieldBuilder implements IteratorAggregate, ArrayAccess, TypesHelperInterface
{
    /**
     * Array of fields definition
     *
     * @var array<string, FieldDefinition>
     */
    protected array $fields = [];

    /**
     * The name of the current field
     */
    protected string $current;


    /**
     * Get all defined fields
     *
     * @return array<string, FieldDefinition>
     */
    public function fields(): array
    {
        return $this->fields;
    }

    /**
     * Specify the autoincrement key for the table.
     *
     * @return $this  This builder instance
     */
    public function autoincrement()
    {
        return $this->primary(Metadata::PK_AUTOINCREMENT);
    }

    /**
     * Specify the sequence key(s) for the table.
     *
     * @return $this  This builder instance
     */
    public function sequence()
    {
        return $this->primary(Metadata::PK_SEQUENCE);
    }

    /**
     * Specify the primary key(s) for the table.
     *
     * @param Metadata::PK_* $type Type of sequence
     *
     * @return $this
     */
    public function primary(string|bool $type = Metadata::PK_AUTO)
    {
        $this->fields[$this->current]['primary'] = $type;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function string(string $name, int $length = 255, ?string $default = null)
    {
        $this->add($name, TypeInterface::STRING, $default);

        return $this->length($length);
    }

    /**
     * {@inheritdoc}
     */
    public function text(string $name, ?string $default = null)
    {
        return $this->add($name, TypeInterface::TEXT, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function integer(string $name, ?int $default = null)
    {
        return $this->add($name, TypeInterface::INTEGER, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function bigint(string $name, string|int|null $default = null)
    {
        return $this->add($name, TypeInterface::BIGINT, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function smallint(string $name, ?int $default = null)
    {
        return $this->add($name, TypeInterface::SMALLINT, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function tinyint(string $name, ?int $default = null)
    {
        return $this->add($name, TypeInterface::TINYINT, $default);
    }

    /**
     * Alias of @see self::double()
     *
     * {@inheritdoc}
     */
    public function float(string $name, ?float $default = null)
    {
        return $this->double($name, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function double(string $name, ?float $default = null)
    {
        return $this->add($name, TypeInterface::DOUBLE, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function decimal(string $name, int|string|float|null $default = null)
    {
        return $this->add($name, TypeInterface::DECIMAL, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function boolean(string $name, ?bool $default = null)
    {
        return $this->add($name, TypeInterface::BOOLEAN, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function date(string $name, string|\DateTimeInterface|null $default = null)
    {
        return $this->add($name, TypeInterface::DATE, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function dateTime(string $name, string|\DateTimeInterface|null $default = null)
    {
        return $this->add($name, TypeInterface::DATETIME, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function dateTimeTz(string $name, string|\DateTimeInterface|null $default = null)
    {
        return $this->add($name, TypeInterface::DATETIMETZ, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function time(string $name, string|\DateTimeInterface|null $default = null)
    {
        return $this->add($name, TypeInterface::TIME, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function timestamp(string $name, string|\DateTimeInterface|null $default = null)
    {
        return $this->add($name, TypeInterface::TIMESTAMP, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function binary(string $name, ?string $default = null)
    {
        return $this->add($name, TypeInterface::BINARY, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function blob(string $name, ?string $default = null)
    {
        return $this->add($name, TypeInterface::BLOB, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function guid(string $name, mixed $default = null)
    {
        return $this->add($name, TypeInterface::GUID, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function json(string $name, mixed $default = null)
    {
        return $this->add($name, TypeInterface::JSON, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function simpleArray(string $name, ?array $default = null)
    {
        return $this->add($name, TypeInterface::TARRAY, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function object(string $name, mixed $default = null)
    {
        return $this->add($name, TypeInterface::OBJECT, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function arrayObject(string $name, ?array $default = null)
    {
        return $this->add($name, TypeInterface::ARRAY_OBJECT, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function searchableArray(string $name, ?array $default = null)
    {
        return $this->add($name, TypeInterface::TARRAY, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function arrayOf(string $name, string $type, ?array $default = null)
    {
        return $this->add($name, $type.'[]', $default);
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
     * Add an enum property, stored as string field on database, using the backed value
     *
     * Note: Enum constraint will not be used on database level. Invalid values will simply be ignored by the ORM.
     *
     * @param string $name The property name
     * @param class-string<T> $enumClass The enum class name
     * @param T|null $default The default value
     *
     * @return $this
     *
     * @template T as BackedEnum
     *
     * @see FieldBuilder::unitEnum() To store the enum name instead of the backed value.
     * @see FieldBuilder::intEnum() For int enum.
     */
    public function stringEnum(string $name, string $enumClass, ?BackedEnum $default = null)
    {
        return $this->add($name, BackedEnumType::STRING_ENUM, $default)->phpClass($enumClass);
    }

    /**
     * Add an enum property, stored as integer field on database, using the backed value
     *
     * Note: Enum constraint will not be used on database level. Invalid values will simply be ignored by the ORM.
     *
     * @param string $name The property name
     * @param class-string<T> $enumClass The enum class name
     * @param T|null $default The default value
     *
     * @return $this
     *
     * @template T as BackedEnum
     *
     * @see FieldBuilder::unitEnum() To store the enum name instead of the backed value.
     * @see FieldBuilder::stringEnum() For string enum.
     */
    public function intEnum(string $name, string $enumClass, ?BackedEnum $default = null)
    {
        return $this->add($name, BackedEnumType::INT_ENUM, $default)->phpClass($enumClass);
    }

    /**
     * Add an enum property, stored as string field on database, using the case name.
     *
     * Note: Enum constraint will not be used on database level. Invalid values will simply be ignored by the ORM.
     *
     * @param string $name The property name
     * @param class-string<T> $enumClass The enum class name
     * @param T|null $default The default value
     *
     * @return $this
     *
     * @template T as UnitEnum
     *
     * @see FieldBuilder::intEnum() For int enum.
     * @see FieldBuilder::stringEnum() For string enum.
     */
    public function unitEnum(string $name, string $enumClass, ?UnitEnum $default = null)
    {
        return $this->add($name, UnitEnumType::UNIT_ENUM, $default)->phpClass($enumClass);
    }

    /**
     * Add a field
     *
     * @param string $name      The property name
     * @param string $type      The repository type
     * @param mixed  $default   The default value for repository
     *
     * @return $this             This builder instance
     */
    public function add(string $name, string $type = TypeInterface::STRING, mixed $default = null)
    {
        $this->current = $name;

        $this->fields[$this->current] = [
            'type'      => $type,
            'default'   => $default,
        ];

        return $this;
    }

    /**
     * Add an embedded field
     *
     * @param string $name The property name
     * @param class-string $classname The embedded class name
     * @param Closure(static):void $resolver Configure the meta for this embedded
     *
     * @return $this This builder instance
     */
    public function embedded(string $name, string $classname, Closure $resolver)
    {
        $builder = new static();
        $resolver($builder);

        $this->fields[$name] = [
            'class'     => $classname,
            'embedded'  => $builder->fields(),
        ];

        return $this;
    }

    /**
     * Add an embedded polymorph field
     *
     * /!\ A discriminator field must be provided, and must not be nillable
     *
     * <code>
     * $fields->polymorph(
     *     'subentity',
     *     [
     *         'user'  => User::class,
     *         'admin' => Admin::class,
     *     ],
     *     function (PolymorphBuilder $builder) {
     *         $builder
     *             ->string('type')->discriminator()
     *             ->string('name')
     *         ;
     *     }
     * );
     * </code>
     *
     * @param string $name The property name
     * @param array<string, class-string> $classMap The class mapping, in form [ discriminatorValue => ClassName ]
     * @param Closure(PolymorphBuilder):void $resolver Configure the meta for this embedded
     *
     * @return $this              This builder instance
     */
    public function polymorph(string $name, array $classMap, Closure $resolver)
    {
        $builder = new PolymorphBuilder();
        $resolver($builder);

        $this->fields[$name] = [
            'class_map'               => $classMap,
            'polymorph'               => true,
            'embedded'                => $builder->fields(),
            'discriminator_field'     => $builder->getDiscriminatorField(),
            'discriminator_attribute' => $builder->getDiscriminatorAttribute(),
        ];

        return $this;
    }

    /**
     * Set length of current string field
     *
     * @param int|null $length        The length of the value
     *
     * @return $this             This builder instance
     */
    public function length(?int $length)
    {
        $this->fields[$this->current]['length'] = $length;

        return $this;
    }

    /**
     * Set comment on current field
     *
     * @param string|null $comment    The comment
     *
     * @return $this             This builder instance
     */
    public function comment(?string $comment)
    {
        $this->fields[$this->current]['comment'] = $comment;

        return $this;
    }

    /**
     * Set alias of current field
     *
     * @param string $alias      The repository name
     *
     * @return $this             This builder instance
     */
    public function alias(string $alias)
    {
        $this->fields[$this->current]['alias'] = $alias;

        return $this;
    }

    /**
     * Set the default value of current field
     *
     * @param mixed $value       The repository name
     *
     * @return $this             This builder instance
     */
    public function setDefault(mixed $value)
    {
        $this->fields[$this->current]['default'] = $value;

        return $this;
    }

    /**
     * Set the precision and scale of a digit
     *
     * @param int $precision     The number of significant digits that are stored for values
     * @param int $scale         The number of digits that can be stored following the decimal point
     *
     * @return $this             This builder instance
     */
    public function precision(int $precision, int $scale = 0)
    {
        $this->fields[$this->current]['precision'] = $precision;
        $this->fields[$this->current]['scale'] = $scale;

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
        $this->fields[$this->current]['nillable'] = $flag;

        return $this;
    }

    /**
     * Set unsigned flag of current field
     *
     * @param bool $flag         Activate/Deactivate unsigned
     *
     * @return $this             This builder instance
     */
    public function unsigned(bool $flag = true)
    {
        $this->fields[$this->current]['unsigned'] = $flag;

        return $this;
    }

    /**
     * Set unique flag of current field
     *
     * @param bool|string $index The index name. True to generate one
     *
     * @return $this             This builder instance
     */
    public function unique(bool|string $index = true)
    {
        $this->fields[$this->current]['unique'] = $index;

        return $this;
    }

    /**
     * Set fixed flag of current field.
     *
     * Fix length of a string
     *
     * @param bool $flag         Activate/Deactivate unique
     *
     * @return $this             This builder instance
     */
    public function fixed(bool $flag = true)
    {
        $this->fields[$this->current]['fixed'] = $flag;

        return $this;
    }

    /**
     * Define the actual type used to store the data.
     *
     * When provided, the schema manager will use this type instead of the default one,
     * the default type will only be used to parse or normalize the value.
     *
     * @param string $type The type name. Should be a constant of TypeInterface.
     *
     * @return $this
     *
     * @see TypeInterface::* constants
     */
    public function storedAs(string $type)
    {
        $this->fields[$this->current]['storageType'] = $type;

        return $this;
    }

    /**
     * Declare the column as JSON type instead of TEXT.
     *
     * @param bool $flag true to use native json type, false to use text type
     *
     * @return $this
     */
    public function useNativeJsonType(bool $flag = true)
    {
        return $this->schemaOption(SqlJsonType::OPTION_USE_NATIVE_JSON, $flag);
    }

    /**
     * Set php class name of the attribute.
     *
     * @param class-string $className  The php class name
     *
     * @return $this             This builder instance
     */
    public function phpClass(string $className)
    {
        return $this->phpOptions('className', $className);
    }

    /**
     * Set date timezone.
     *
     * @param string $timezone   The date timezone
     *
     * @return $this             This builder instance
     */
    public function timezone(string $timezone)
    {
        return $this->phpOptions('timezone', $timezone);
    }

    /**
     * Convert JSON objects are as associative arrays instead of stdClass.
     * By default this flag is enabled, and should be disabled manually by setting it to false.
     *
     * @param bool $flag true to convert to array, false to keep stdClass
     *
     * @return $this
     *
     * @see json_decode() The assoc parameter
     */
    public function jsonObjectAsArray(bool $flag = true)
    {
        return $this->phpOptions(SqlJsonType::OPTION_OBJECT_AS_ARRAY, $flag);
    }

    /**
     * Set a php options use by type when database value is transformed to php.
     *
     * @param string $key The key option
     * @param mixed $value The value
     *
     * @return $this             This builder instance
     */
    public function phpOptions(string $key, mixed $value)
    {
        $this->fields[$this->current]['phpOptions'][$key] = $value;

        return $this;
    }

    //---- methods for schema

    /**
     * Set schema options.
     *
     * {@see \Doctrine\DBAL\Schema\Table} for the detail
     *
     * @param array $options     The array of options
     *
     * @return $this             This builder instance
     */
    public function schemaOptions(array $options)
    {
        $this->fields[$this->current]['customSchemaOptions'] = $options;

        return $this;
    }

    /**
     * Add a single schema option.
     *
     * @param string $key The option name
     * @param mixed $value The option value
     *
     * @return $this This builder instance
     *
     * @see \Doctrine\DBAL\Schema\Table for the detail
     */
    public function schemaOption(string $key, mixed $value)
    {
        $this->fields[$this->current]['customSchemaOptions'][$key] = $value;

        return $this;
    }

    /**
     * Set platform options.
     *
     * {@see \Doctrine\DBAL\Schema\Table} for the detail
     *
     * @param array $options     The array of options
     *
     * @return $this             This builder instance
     */
    public function platformOptions(array $options)
    {
        $this->fields[$this->current]['platformOptions'] = $options;

        return $this;
    }

    /**
     * Set a custom definition for the schema of this field
     *
     * @param string $definition  The schema definition
     *
     * @return $this              This builder instance
     */
    public function definition(string $definition)
    {
        $this->fields[$this->current]['columnDefinition'] = $definition;

        return $this;
    }

    /**
     * Mark the current field as virtual
     *
     * A virtual field is a read only value generated by PHP using property hook.
     * The value will be written to the database, but will be not used for hydrating the entity.
     *
     * @return $this
     */
    public function virtual(bool $flag = true)
    {
        $this->fields[$this->current]['virtual'] = $flag;

        return $this;
    }

    /**
     * Change current field
     *
     * @param string $name
     *
     * @return $this
     */
    public function field(string $name)
    {
        if (!isset($this->fields[$name])) {
            throw new \RuntimeException('Field ' . $name . ' not found');
        }

        $this->current = $name;

        return $this;
    }

    /**
     * Replace fields by another builder's fields
     *
     * @param FieldBuilder|iterable<string, FieldDefinition> $fields
     *
     * @return $this
     */
    public function fill(iterable $fields)
    {
        if ($fields instanceof FieldBuilder) {
            $this->fields = $fields->fields;
        } elseif (is_array($fields)) {
            $this->fields = $fields;
        } else {
            $this->fields = iterator_to_array($fields);
        }

        return $this;
    }

    //---- interator interface

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Iterator
    {
        return new ArrayIterator($this->fields);
    }

    //---- array access interface

    /**
     * {@inheritdoc}
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->fields[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet(mixed $offset): array
    {
        return $this->fields[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        // not allowed
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset(mixed $offset): void
    {
        // not allowed
    }
}
