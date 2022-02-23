<?php

namespace Bdf\Prime\Mapper\Builder;

/**
 * Builder for mapper indexes
 *
 * <code>
 * $builder
 *     ->add('my_index')->on('username')->unique()
 *     ->add()->on(['date' => ['order' => 'DESC'], 'type' => ['length' => 3])
 * ;
 * </code>
 */
class IndexBuilder
{
    /**
     * Store formatted indexes
     *
     * @var array
     */
    private $indexes = [];

    /**
     * The current index name or offset
     *
     * @var string|integer
     */
    private $current;

    /**
     * Last index offset for auto-generated names
     *
     * @var int
     */
    private $index = 0;


    /**
     * Add a new index
     *
     * @param string|null $name The index name, or null for auto-generate the name
     *
     * @return $this
     */
    public function add($name = null)
    {
        if ($name === null) {
            $name = $this->index++;
        }

        $this->indexes[$name] = ['fields' => []];
        $this->current = $name;

        return $this;
    }

    /**
     * Declare field on index
     * Multiple call to on() can be performed on the same index, an will append new fields on the index
     *
     * Available field options :
     * - length : The field indexing prefix length, for text fields
     * - order  : The sort order of the field. Not supported by MySQL
     *
     * <code>
     * $builder
     *     ->add()->on('name', ['length' => 12]) // Indexing only the 12 firsts chars of the name
     *
     *     // Use with array
     *     ->add()->on([
     *         'date' => ['order' => 'DESC'], // Indexing date with custom order
     *         'location' // Indexing location without custom options
     *     ])
     *
     *     // Same as above
     *     ->add()->on('date', ['order' => 'DESC'])->on('location')
     * ;
     * </code>
     *
     * @param string|array $field The field name, or array of fields with options
     * @param array $options The field option, if the first parameter is a string
     *
     * @return $this
     */
    public function on($field, array $options = [])
    {
        if (is_string($field)) {
            $this->indexes[$this->current]['fields'][$field] = $options;

            return $this;
        }

        foreach ($field as $name => $options) {
            if (is_string($name)) {
                $this->on($name, $options);
            } else {
                $this->on($options);
            }
        }

        return $this;
    }

    /**
     * Add a flag to the index
     * A flag is an option with value of true
     *
     * Actual supported flags by MySQL :
     * - fulltext
     * - spatial
     * - unique
     *
     * @param string $name The flag name to set
     *
     * @return $this
     */
    public function flag($name)
    {
        $this->indexes[$this->current][$name] = true;

        return $this;
    }

    /**
     * Add an option to the index
     *
     * Actual supported options :
     * - where : For partial index selector (not supported by MySQL)
     * - lengths : Define the fields indexing prefix length, prefer use field option instead
     *
     * @param string $name The option name
     * @param mixed $value The option value
     *
     * @return $this
     */
    public function option($name, $value)
    {
        $this->indexes[$this->current][$name] = $value;

        return $this;
    }

    /**
     * Add a unique constraint to the current index
     *
     * @return $this
     */
    public function unique()
    {
        return $this->flag('unique');
    }

    /**
     * Build indexes metadata
     *
     * @return array
     */
    public function build()
    {
        return $this->indexes;
    }
}
