<?php

namespace Bdf\Prime\Mapper\Builder;

use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Types\TypeInterface;
use Bdf\Prime\Types\TypesHelperInterface;

/**
 * FieldBuilder
 *
 * @package Bdf\Prime\Mapper\Builder
 */
class FieldBuilder implements \ArrayAccess, \IteratorAggregate, TypesHelperInterface
{
    /**
     * Array of fields definition
     *
     * @var array
     */
    protected $fields = [];

    /**
     * The name of the current field
     *
     * @var string
     */
    protected $current;


    /**
     * Get all defined fields
     *
     * @return array
     */
    public function fields()
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
    public function primary($type = Metadata::PK_AUTO)
    {
        $this->fields[$this->current]['primary'] = $type;
        
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function string($name, $length = 255, $default = null)
    {
        $this->add($name, TypeInterface::STRING, $default);

        return $this->length($length);
    }

    /**
     * {@inheritdoc}
     */
    public function text($name, $default = null)
    {
        return $this->add($name, TypeInterface::TEXT, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function integer($name, $default = null)
    {
        return $this->add($name, TypeInterface::INTEGER, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function bigint($name, $default = null)
    {
        return $this->add($name, TypeInterface::BIGINT, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function smallint($name, $default = null)
    {
        return $this->add($name, TypeInterface::SMALLINT, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function tinyint($name, $default = null)
    {
        return $this->add($name, TypeInterface::TINYINT, $default);
    }

    /**
     * Alias of @see self::double()
     *
     * {@inheritdoc}
     */
    public function float($name, $default = null)
    {
        return $this->double($name, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function double($name, $default = null)
    {
        return $this->add($name, TypeInterface::DOUBLE, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function decimal($name, $default = null)
    {
        return $this->add($name, TypeInterface::DECIMAL, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function boolean($name, $default = null)
    {
        return $this->add($name, TypeInterface::BOOLEAN, $default !== null ? (bool)$default : null);
    }

    /**
     * {@inheritdoc}
     */
    public function date($name, $default = null)
    {
        return $this->add($name, TypeInterface::DATE, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function dateTime($name, $default = null)
    {
        return $this->add($name, TypeInterface::DATETIME, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function dateTimeTz($name, $default = null)
    {
        return $this->add($name, TypeInterface::DATETIMETZ, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function time($name, $default = null)
    {
        return $this->add($name, TypeInterface::TIME, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function timestamp($name, $default = null)
    {
        return $this->add($name, TypeInterface::TIMESTAMP, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function binary($name, $default = null)
    {
        return $this->add($name, TypeInterface::BINARY, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function blob($name, $default = null)
    {
        return $this->add($name, TypeInterface::BLOB, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function guid($name, $default = null)
    {
        return $this->add($name, TypeInterface::GUID, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function json($name, $default = null)
    {
        return $this->add($name, TypeInterface::JSON, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function simpleArray($name, $default = null)
    {
        return $this->add($name, TypeInterface::TARRAY, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function object($name, $default = null)
    {
        return $this->add($name, TypeInterface::OBJECT, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function arrayObject($name, $default = null)
    {
        return $this->add($name, TypeInterface::ARRAY_OBJECT, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function searchableArray($name, $default = null)
    {
        return $this->add($name, TypeInterface::TARRAY, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function arrayOf($name, $type, $default = null)
    {
        return $this->add($name, $type.'[]', $default);
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
     * Add a field
     *
     * @param string $name      The property name
     * @param string $type      The repository type
     * @param mixed  $default   The default value for repository
     *
     * @return $this             This builder instance
     */
    public function add($name, $type = TypeInterface::STRING, $default = null)
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
     * @param string   $name      The property name
     * @param string   $classname The embedded class name
     * @param \Closure $resolver  Configure the meta for this embedded
     *
     * @return $this              This builder instance
     */
    public function embedded($name, $classname, \Closure $resolver)
    {
        $builder = new static;
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
     * @param array $classMap The class mapping, in form [ discriminatorValue => ClassName ]
     * @param \Closure $resolver Configure the meta for this embedded
     *
     * @return $this              This builder instance
     */
    public function polymorph($name, $classMap, \Closure $resolver)
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
     * @param int $length        The length of the value
     *
     * @return $this             This builder instance
     */
    public function length($length)
    {
        $this->fields[$this->current]['length'] = $length;

        return $this;
    }

    /**
     * Set comment on current field
     *
     * @param string $comment    The comment
     *
     * @return $this             This builder instance
     */
    public function comment($comment)
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
    public function alias($alias)
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
    public function setDefault($value)
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
    public function precision($precision, $scale = 0)
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
    public function nillable($flag = true)
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
    public function unsigned($flag = true)
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
    public function unique($index = true)
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
    public function fixed($flag = true)
    {
        $this->fields[$this->current]['fixed'] = $flag;

        return $this;
    }

    /**
     * Set php class name of the attribute.
     *
     * @param string $className  The php class name
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
     * Set a php options use by type when database value is transformed to php.
     *
     * @param string $key        The key option
     * @param string $value      The value
     *
     * @return $this             This builder instance
     */
    public function phpOptions($key, $value)
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
    public function definition($definition)
    {
        $this->fields[$this->current]['columnDefinition'] = $definition;

        return $this;
    }

    /**
     * Change current field
     *
     * @param string $name
     *
     * @return $this
     */
    public function field($name)
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
     * @param FieldBuilder $builder
     *
     * @return $this
     */
    public function fill(FieldBuilder $builder)
    {
        $this->fields = $builder->fields;

        return $this;
    }

    //---- interator interface
    
    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->fields);
    }
    
    //---- array access interface
    
    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->fields[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($key)
    {
        return $this->fields[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($key, $value)
    {
        // not allowed
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        // not allowed
    }
}
