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
     * @param mixed $default The default value for repository
     *
     * @return $this            This builder instance
     */
    public function string($name, $length = 255, $default = null);

    /**
     * Add a text field
     *
     * @param string $name The property name
     * @param mixed $default The default value for repository
     *
     * @return $this            This builder instance
     */
    public function text($name, $default = null);

    /**
     * Add an integer field
     *
     * @param string $name The property name
     * @param int $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function integer($name, $default = null);

    /**
     * Add an bigint field
     *
     * @param string $name The property name
     * @param string $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function bigint($name, $default = null);

    /**
     * Add a smallint field
     *
     * @param string $name The property name
     * @param int $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function smallint($name, $default = null);

    /**
     * Add a tinyint field
     *
     * @param string $name The property name
     * @param int $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function tinyint($name, $default = null);

    /**
     * Add a float field.
     * Alias of @see FieldBuilder::double()
     *
     * @param string $name The property name
     * @param float $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function float($name, $default = null);

    /**
     * Add a double field
     *
     * @param string $name The property name
     * @param float $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function double($name, $default = null);

    /**
     * Add a decimal field
     *
     * @param string $name The property name
     * @param float $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function decimal($name, $default = null);

    /**
     * Add a boolean field
     *
     * @param string $name The property name
     * @param bool $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function boolean($name, $default = null);

    /**
     * Add a date field
     *
     * @param string $name The property name
     * @param string $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function date($name, $default = null);

    /**
     * Add a datetime field
     *
     * @param string $name The property name
     * @param string $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function dateTime($name, $default = null);

    /**
     * Add a datetimeTz field
     *
     * @param string $name The property name
     * @param string $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function dateTimeTz($name, $default = null);

    /**
     * Add a time field
     *
     * @param string $name The property name
     * @param string $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function time($name, $default = null);

    /**
     * Add a timestamp field
     *
     * @param string $name The property name
     * @param string $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function timestamp($name, $default = null);

    /**
     * Add a binary field
     *
     * @param string $name The property name
     * @param string $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function binary($name, $default = null);

    /**
     * Add a blob field
     *
     * @param string $name The property name
     * @param string $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function blob($name, $default = null);

    /**
     * Add a guid field
     *
     * @param string $name The property name
     * @param string $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function guid($name, $default = null);

    /**
     * Add a json array field
     *
     * @param string $name The property name
     * @param string $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function json($name, $default = null);

    /**
     * Add a simple array field
     *
     * @param string $name The property name
     * @param string $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function simpleArray($name, $default = null);

    /**
     * Add a stdClass object
     *
     * @param string $name The property name
     * @param string $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function object($name, $default = null);

    /**
     * Add a associative array
     *
     * @param string $name The property name
     * @param string $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function arrayObject($name, $default = null);

    /**
     * Add a searchable array field
     *
     * @param string $name The property name
     * @param string $default The default value for repository
     *
     * @return $this             This builder instance
     */
    public function searchableArray($name, $default = null);

    /**
     * Add a typed array field
     *
     * @param string $name The field name
     * @param string $type The array type
     * @param string $default The default field value
     *
     * @return $this The builder instance
     */
    public function arrayOf($name, $type, $default = null);

    /**
     * Add an int array field
     *
     * @param string $name The field name
     * @param string $default The default field value
     *
     * @return $this The builder instance
     */
    public function arrayOfInt($name, $default = null);

    /**
     * Add a double array field
     *
     * @param string $name The field name
     * @param string $default The default field value
     *
     * @return $this The builder instance
     */
    public function arrayOfDouble($name, $default = null);

    /**
     * Add an array of DateTime field
     *
     * @param string $name The field name
     * @param string $default The default field value
     *
     * @return $this The builder instance
     */
    public function arrayOfDateTime($name, $default = null);
}
