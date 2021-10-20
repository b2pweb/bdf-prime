<?php

namespace Bdf\Prime\Types;

use Bdf\Prime\Mapper\Builder\FieldBuilder;


/**
 * Interface for build typed fields
 *
 * All method must return $this
 * And all platform should handle all listed types
 */
interface TypesHelperInterface
{
    /**
     * Add a string field
     *
     * @param string $name The property name
     * @param int $length The length of value
     * @param string|null $default The default value for repository
     *
     * @return $this            This builder instance
     */
    public function string(string $name, int $length = 255, ?string $default = null);

    /**
     * Add a text field
     *
     * @param string $name The property name
     * @param string|null $default The default value for repository
     *
     * @return $this            This builder instance
     */
    public function text(string $name, ?string $default = null);

    /**
     * Add an integer (4 bytes) field
     *
     * @param string $name The property name
     * @param int|null $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function integer(string $name, ?int $default = null);

    /**
     * Add an bigint (8 bytes) field
     *
     * @param string $name The property name
     * @param numeric-string|int|null $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function bigint(string $name, $default = null);

    /**
     * Add a smallint (2 bytes) field
     *
     * @param string $name The property name
     * @param int|null $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function smallint(string $name, ?int $default = null);

    /**
     * Add a tinyint (1 byte) field
     *
     * @param string $name The property name
     * @param int|null $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function tinyint(string $name, ?int $default = null);

    /**
     * Add a float field.
     * Alias of @see FieldBuilder::double()
     *
     * @param string $name The property name
     * @param float|null $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function float(string $name, ?float $default = null);

    /**
     * Add a double field
     *
     * @param string $name The property name
     * @param float|null $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function double(string $name, ?float $default = null);

    /**
     * Add a decimal field
     *
     * @param string $name The property name
     * @param numeric|null $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function decimal(string $name, $default = null);

    /**
     * Add a boolean field
     *
     * @param string $name The property name
     * @param bool|null $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function boolean(string $name, ?bool $default = null);

    /**
     * Add a date field
     *
     * @param string $name The property name
     * @param string|\DateTimeInterface|null $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function date(string $name, $default = null);

    /**
     * Add a datetime field
     *
     * @param string $name The property name
     * @param string|\DateTimeInterface|null $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function dateTime(string $name, $default = null);

    /**
     * Add a datetimeTz field
     *
     * @param string $name The property name
     * @param string|\DateTimeInterface|null $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function dateTimeTz(string $name, $default = null);

    /**
     * Add a time field
     *
     * @param string $name The property name
     * @param string|\DateTimeInterface|null $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function time(string $name, $default = null);

    /**
     * Add a timestamp field
     *
     * @param string $name The property name
     * @param string|\DateTimeInterface|null $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function timestamp(string $name, $default = null);

    /**
     * Add a binary field
     *
     * @param string $name The property name
     * @param string|null $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function binary(string $name, ?string $default = null);

    /**
     * Add a blob field
     *
     * @param string $name The property name
     * @param string|null $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function blob(string $name, ?string $default = null);

    /**
     * Add a guid field
     *
     * @param string $name The property name
     * @param string|mixed|null $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function guid(string $name, $default = null);

    /**
     * Add a json array field
     *
     * @param string $name The property name
     * @param mixed|null $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function json(string $name, $default = null);

    /**
     * Add a simple array field
     *
     * @param string $name The property name
     * @param list<mixed>|null $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function simpleArray(string $name, ?array $default = null);

    /**
     * Add a stdClass object
     *
     * @param string $name The property name
     * @param mixed|null $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function object(string $name, $default = null);

    /**
     * Add a associative array
     *
     * @param string $name The property name
     * @param array|null $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function arrayObject(string $name, ?array $default = null);

    /**
     * Add a searchable array field
     *
     * @param string $name The property name
     * @param array|null $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function searchableArray(string $name, ?array $default = null);

    /**
     * Add a typed array field
     *
     * @param string $name The field name
     * @param string $type The array type
     * @param array|null $default The default field value
     *
     * @return $this The builder instance
     */
    public function arrayOf(string $name, string $type, ?array $default = null);

    /**
     * Add an int array field
     *
     * @param string $name The field name
     * @param int[]|null $default The default field value
     *
     * @return $this The builder instance
     */
    public function arrayOfInt(string $name, ?array $default = null);

    /**
     * Add a double array field
     *
     * @param string $name The field name
     * @param float[]|null $default The default field value
     *
     * @return $this The builder instance
     */
    public function arrayOfDouble(string $name, ?array $default = null);

    /**
     * Add an array of DateTime field
     *
     * @param string $name The field name
     * @param array|null $default The default field value
     *
     * @return $this The builder instance
     */
    public function arrayOfDateTime(string $name, ?array $default = null);
}
