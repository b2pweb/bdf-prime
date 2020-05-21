<?php

namespace Bdf\Prime\Mapper;

use Bdf\Prime\Entity\Instantiator\InstantiatorInterface;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Relations\Builder\RelationBuilder;
use Bdf\Prime\Relations\Relation;
use stdClass;

/**
 * Metadata
 * 
 * @todo gerer le nom de la base de données si non fourni
 * @todo exception si aucune primary ou unique n'a été définit ?
 * @todo doit on injecter si private ??
 *
 * @package Bdf\Prime\Mapper
 */
class Metadata
{
    /* constantes définissant le type de primary key */
    const PK_AUTOINCREMENT = 'autoincrement';
    const PK_AUTO = true;
    const PK_SEQUENCE = 'sequence';
    
    /**
     * The expected entity classname
     *
     * @var string
     */
    public $entityName;

    /**
     * The instantiator hint
     *
     * @var int
     */
    public $instantiatorHint;

    /**
     * The class name to use
     *
     * if the class name does not exist, a stdClass will be used
     *
     * @var string
     */
    public $entityClass;

    /**
     * The property accessor class name to use
     * Usefull only for building metadata
     *
     * @var string
     */
    public $propertyAccessorClass;

    /**
     * @var string
     */
    public $connection;
    
    /**
     * @var string
     */
    public $database;
    
    /**
     * @var string
     */
    public $table;
    
    /**
     * @var boolean
     */
    public $useQuoteIdentifier;
    
    /**
     * @var array
     */
    public $tableOptions = [];
    
    /**
     * The indexes
     * Format :
     * [
     *    [index_name] => [
     *         'fields' => [
     *             'field1' => [
     *                 'fieldOption' => 'optValue',
     *                 ...
     *             ],
     *             ...
     *         ],
     *         'unique' => true,
     *         'option1' => 'value1',
     *         'option2' => 'value2',
     *         ...
     *    ]
     * ]
     *
     * With :
     * - index_name : The index name as string for named index, or integer offset for generated name
     * - 'fields' : Array of fields where the index is applied. The fields are the database fields
     * - 'unique' : Not set for simple indexes, the value is true for indicate a unique constraint on the index
     * - options : Key/value index options, depends of the database platform and driver
     * - fieldOption : Option related to the field, like length or sort order
     *
     * @var array
     */
    public $indexes = [];
    
    /**
     * @var array
     */
    public $sequence = [
        'connection'   => null,
        'table'        => null,
        'column'       => null,
        'options' => [],
    ];
    
    /**
     * @var array
     */
    public $fields = [];
    
    /**
     * @var array
     */
    public $attributes = [];
    
    /**
     * @var array
     */
    public $embeddeds = [];
    
    /**
     * @var array
     */
    public $primary = [
        'type'          => self::PK_AUTO,
        'attributes'    => [],
        'fields'        => [],
    ];
    
    /**
     * The repository global constraints
     * 
     * @var array
     */
    public $constraints = [];
    
    /**
     * Flag indiquant que le meta a déjà été construit
     * 
     * @var bool
     */
    protected $built = false;

    /**
     * Relations that must be loaded eagerly
     *
     * @var array
     */
    public $eagerRelations = [];
    
    /**
     * Get entity class name
     * 
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * Is metadata built
     * 
     * @return bool
     */
    public function isBuilt()
    {
        return $this->built;
    }
    
    /**
     * Get connection identifier from locator
     * 
     * @return string
     */
    public function connection()
    {
        return $this->connection;
    }
    
    /**
     * Get database name
     * 
     * @return string
     */
    public function database()
    {
        return $this->database;
    }
    
    /**
     * Get table name
     * 
     * @return string
     */
    public function table()
    {
        return $this->table;
    }
    
    /**
     * Get table options
     * 
     * @return array
     */
    public function tableOptions()
    {
        return $this->tableOptions;
    }
    
    /**
     * Get indexes
     * 
     * @return array
     */
    public function indexes()
    {
        return $this->indexes;
    }
    
    /**
     * Get embedded meta
     * 
     * @return array
     */
    public function embeddeds()
    {
        return $this->embeddeds;
    }
    
    /**
     * Get attribute embedded meta
     *
     * @param string $attribute
     *
     * @return array
     */
    public function embedded($attribute)
    {
        return isset($this->embeddeds[$attribute])
            ? $this->embeddeds[$attribute]
            : null;
    }
    
    /**
     * Get attribute or field metadata
     * 
     * @param string $key
     * @param string $type
     *
     * @return array
     */
    public function meta($key, $type = 'attributes')
    {
        if (isset($this->{$type}[$key])) {
            return $this->{$type}[$key];
        }
        
        return null;
    }
    
    /**
     * Returns primary attributes | fields | type
     * 
     * @param string $type
     *
     * @return array
     */
    public function primary($type = 'attributes')
    {
        return $this->primary[$type];
    }
    
    /**
     * Returns metadata for first primary key
     * 
     * @return array
     */
    public function firstPrimaryMeta()
    {
        list($primary) = $this->primary['attributes'];
        
        return $this->attributes[$primary];
    }
    
    /**
     * Returns all metadata for primary key
     * 
     * @return array
     */
    public function primaryMeta()
    {
        $meta = [];
        
        foreach ($this->primary['attributes'] as $attribute) {
            $meta[$attribute] = $this->attributes[$attribute];
        }
        
        return $meta;
    }
    
    /**
     * Returns sequence info
     * 
     * @param string $key
     *
     * @return array|string
     */
    public function sequence($key = null)
    {
        return $key === null
            ? $this->sequence
            : $this->sequence[$key];
    }
    
    /**
     * Check if key is primary
     * 
     * @param string $key
     * @param string $type
     *
     * @return bool
     */
    public function isPrimary($key, $type = 'attributes')
    {
        return in_array($key, $this->primary[$type]);
    }
    
    /**
     * Is primary key an auto increment
     * 
     * @return bool
     */
    public function isAutoIncrementPrimaryKey()
    {
        return $this->primary['type'] === self::PK_AUTOINCREMENT;
    }
    
    /**
     * Is a sequence generated primary key
     * 
     * @return bool
     */
    public function isSequencePrimaryKey()
    {
        return $this->primary['type'] === self::PK_SEQUENCE;
    }

    /**
     * Is a foreign key as primary key
     *
     * @return bool
     */
    public function isForeignPrimaryKey()
    {
        return $this->primary['type'] === self::PK_AUTO;
    }

    /**
     * The primary key has multiple fields
     * 
     * @return bool
     */
    public function isCompositePrimaryKey()
    {
        return count($this->primary['attributes']) > 1;
    }

    /**
     * Get fields metadata
     * 
     * @return array
     */
    public function fields()
    {
        return $this->fields;
    }

    public function eagerRelations()
    {
        return $this->eagerRelations;
    }
    
    /**
     * Does field exist
     * 
     * @param string $field
     *
     * @return bool
     */
    public function fieldExists($field)
    {
        return isset($this->fields[$field]);
    }
    
    /**
     * Get field type
     * 
     * @param string $field
     *
     * @return string
     */
    public function fieldType($field)
    {
        return $this->fields[$field]['type'];
    }
    
    /**
     * Get attributes
     *
     * @return array
     */
    public function attributes()
    {
        return $this->attributes;
    }
    
    /**
     * Does attribute exist
     * 
     * @param string $attribute
     *
     * @return bool
     */
    public function attributeExists($attribute)
    {
        return isset($this->attributes[$attribute]);
    }
    
    /**
     * Get attribute type
     * 
     * @param string $attribute
     *
     * @return string
     */
    public function attributeType($attribute)
    {
        return $this->attributes[$attribute]['type'];
    }
    
    /**
     * Get field from attribute alias if exists
     * 
     * @param string $attribute
     *
     * @return string
     */
    public function fieldFrom($attribute)
    {
        return $this->attributes[$attribute]['field'];
    }
    
    /**
     * Get attribute from field alias if exists
     * 
     * @param string $field
     *
     * @return string
     */
    public function attributeFrom($field)
    {
        return $this->fields[$field]['attribute'];
    }
    
    /**
     * @param Mapper $mapper
     */
    public function build($mapper)
    {
        if (!$this->built) {
            $this->entityName = $mapper->getEntityClass();
            $this->useQuoteIdentifier = $mapper->hasQuoteIdentifier();
            $this->constraints = $mapper->constraints();
            $this->propertyAccessorClass = $mapper->getPropertyAccessorClass();
            $this->entityClass = $this->getExistingClassName($this->entityName);
            $this->instantiatorHint = $this->getInstantiatorHint($this->entityClass);

            $this->buildSchema($mapper->schema());
            $this->buildFields($mapper->fields());
            $this->buildSequence($mapper->sequence());
            $this->buildIndexes($mapper->indexes());
            $this->buildRelations($mapper->relations());

            $this->built = true;
        }
    }

    /**
     * Get the classname if exists
     *
     * @param string $entityClass
     *
     * @return string
     */
    private function getExistingClassName($entityClass)
    {
        if ($entityClass === stdClass::class || !class_exists($entityClass)) {
            return stdClass::class;
        }

        return $entityClass;
    }

    /**
     * Guess the instantiator hint
     *
     * This method will check the class constructor. If it has one non optional parameter
     * it will return no hint. Otherwise the default constructor hiint will be returned.
     *
     * @param string $className
     *
     * @return null|string
     */
    private function getInstantiatorHint($className)
    {
        if ($className === stdClass::class) {
            return InstantiatorInterface::USE_CONSTRUCTOR_HINT;
        }

        try {
            $constructor = (new \ReflectionClass($className))->getConstructor();

            if ($constructor !== null) {
                foreach ($constructor->getParameters() as $parameter) {
                    if (!$parameter->isOptional()) {
                        return null;
                    }
                }
            }

            return InstantiatorInterface::USE_CONSTRUCTOR_HINT;
        } catch (\ReflectionException $exception) {
            return null;
        }
    }

    /**
     * Build schema metadata
     *
     * @param array $schema
     */
    private function buildSchema(array $schema)
    {
        $schema += [
            'connection'   => null,
            'database'     => null,
            'table'        => null,
            'tableOptions' => [],
        ];
        
        //TODO Comment recuperer la database si non fournie
        //$service->connection($this->connection)->getDatabase();
        
        $this->connection   = $schema['connection'];
        $this->database     = $schema['database'];
        $this->table        = $schema['table'];
        $this->tableOptions = $schema['tableOptions'];
    }
    
    /**
     * Builds fields metadata
     * 
     * @param array|FieldBuilder $fields
     * @param array              $embeddedMeta
     */
    private function buildFields($fields, $embeddedMeta = null)
    {
        foreach ($fields as $attribute => $meta) {
            if (isset($meta['embedded'])) {
                $this->buildFields(
                    $meta['embedded'], 
                    empty($meta['polymorph'])
                        ? $this->buildEmbedded($attribute, $meta['class'], $embeddedMeta)
                        : $this->buildPolymorph($attribute, $meta, $embeddedMeta)
                );
            } else {
                $this->buildField($attribute, $meta, $embeddedMeta);
            }
        }
    }
    
    /**
     * Builds indexes metadata
     * 
     * @param array $indexes
     */
    private function buildIndexes(array $indexes)
    {
        $this->buildSimpleIndexes($indexes);
        $this->buildIndexesFromFields();
    }

    /**
     * Build simple indexes declared on mapper
     * The field names will be mapped with DB attributes if exists, and will be normalized into the new format
     *
     * Supports old format :
     * [
     *     'index_name' => ['field1', 'field2', ...],
     *     ...
     * ]
     *
     * And new format :
     * [
     *     'index_name' => [
     *         'fields' => [
     *             'field1' => [fieldOptions],
     *             'field2' => [fieldOptions],
     *             ...
     *         ],
     *         'unique' => true,
     *         'option' => 'value',
     *         // More options
     *     ],
     *     ...
     * ]
     *
     * @param array $indexes
     */
    private function buildSimpleIndexes(array $indexes)
    {
        foreach ($indexes as $name => $index) {
            // Legacy format compatibility
            if (!isset($index['fields'])) {
                $fields = [];

                foreach ($index as $field) {
                    $fields[$field] = [];
                }

                $index = ['fields' => $fields];
            }

            $fields = [];

            // Map the field name if exists
            foreach ($index['fields'] as $field => $options) {
                if (isset($this->attributes[$field])) {
                    $field = $this->attributes[$field]['field'];
                }

                $fields[$field] = $options;
            }

            $index['fields'] = $fields;

            $this->indexes[$name] = $index;
        }
    }

    /**
     * Extract unique indexes from field declaration
     * Must be called after @see Metadata::buildFields()
     *
     * The unique indexes will follow same format as simple indexes, but with the option 'unique' set as true
     */
    private function buildIndexesFromFields()
    {
        foreach ($this->fields as $field => $meta) {
            if (empty($meta['unique'])) {
                continue;
            }

            if (is_string($meta['unique'])) {
                if (isset($this->indexes[$meta['unique']])) {
                    $this->indexes[$meta['unique']]['fields'][$field] = [];
                } else {
                    $this->indexes[$meta['unique']] = [
                        'fields' => [$field => []],
                        'unique' => true,
                    ];
                }
            } else {
                $this->indexes[] = [
                    'fields' => [$field => []],
                    'unique' => true,
                ];
            }
        }
    }
    
    /**
     * Build Embedded meta
     * 
     * @param string $attribute
     * @param string $class
     * @param array $embeddedMeta
     *
     * @return array
     */
    private function buildEmbedded($attribute, $class, $embeddedMeta)
    {
        if ($embeddedMeta === null) {
            $attributePath = $attribute;
            $path          = 'root';
            $paths         = [$attributePath];
        } else {
            $attributePath = $embeddedMeta['path'].'.'.$attribute;
            $path          = $embeddedMeta['path'];
            $paths         = array_merge($embeddedMeta['paths'], [$attributePath]);
        }

        return $this->embeddeds[$attributePath] = [
            'class'           => $this->getExistingClassName($class),
            'hint'            => $this->getInstantiatorHint($class),
            'path'            => $attributePath,
            'parentPath'      => $path,
            'paths'           => $paths,
        ];
    }

    /**
     * Build a polymorph embedded
     *
     * @param string $attribute
     * @param array $meta
     * @param array $embeddedMeta
     *
     * @return array
     */
    private function buildPolymorph($attribute, array $meta, $embeddedMeta)
    {
        if ($embeddedMeta === null) {
            $attributePath = $attribute;
            $path          = 'root';
            $paths         = [$attributePath];
        } else {
            $attributePath = $embeddedMeta['path'].'.'.$attribute;
            $path          = $embeddedMeta['path'];
            $paths         = array_merge($embeddedMeta['paths'], [$attributePath]);
        }

        $hints = [];

        foreach ($meta['class_map'] as $class) {
            $hints[$class] = $this->getInstantiatorHint($class);
        }

        return $this->embeddeds[$attributePath] = [
            'path'       => $attributePath,
            'parentPath' => $path,
            'paths'      => $paths,
            'hints'      => $hints
        ] + $meta;
    }

    /**
     * Build mapped embedded meta
     *
     * @todo  parcourir la map pour construire les differents embeddeds 'attribute-discriminatorValue'
     *
     * @param string $attribute
     * @param array  $map
     * @param string $discriminator
     * @param array  $embeddedMeta
     */
    private function buildMappedEmbedded($attribute, $map, $discriminator, $embeddedMeta = null)
    {
        $entity = reset($map);

        if (is_string($entity)) {
            list($entity) = Relation::parseEntity($entity);
        } else {
            $entity = $entity['entity'];
        }

        $this->buildEmbedded($attribute, $entity, $embeddedMeta);
    }

    /**
     * Build embedded relations missing in embedded
     * 
     * @param array|RelationBuilder $relations
     */
    private function buildRelations($relations)
    {
        foreach ($relations as $attribute => $relation) {

            // il est possible de déclarer des relations sans attribut sur l'entity (cas de grosse collection)
            if (!empty($relation['detached'])) {
                continue;
            }

            if (isset($relation['mode']) && $relation['mode'] === RelationBuilder::MODE_EAGER) {
                $this->eagerRelations[$attribute] = [];
            }

            // si l'attribut est déjà définit, car embedded
            if (isset($this->embeddeds[$attribute])) {
                continue;
            }

            if (isset($relation['map'])) {
                $this->buildMappedEmbedded($attribute, $relation['map'], $relation['discriminator']);
                continue;
            }

            // si entity n'est pas definit
            if (!isset($relation['entity'])) {
                continue;
            }

            // Attention: un embedded relation doit appartenir à l'entity.
            // Ne pas pas etre dans un object de entity
            // C'est pour cette raison que le 3eme parametre est à null
            $this->buildEmbedded($attribute, $relation['entity'], null);
        }

        // Preformatage des eager relations
        $this->eagerRelations = Relation::sanitizeRelations($this->eagerRelations);
    }
    
    /**
     * Build field meta
     * 
     * @param string $attribute
     * @param array $meta
     * @param array $embeddedMeta
     */
    private function buildField($attribute, $meta, $embeddedMeta)
    {
        //concatenation de l'attribut parent
        if ($embeddedMeta === null) {
            $attributePath = $attribute;
            $path          = null;
        } else {
            $attributePath = $embeddedMeta['path'] . '.' . $attribute;
            $path          = $embeddedMeta['path'];
        }

        // TODO call 'Inflector::tableize($attributePath) ?'
        $field = isset($meta['alias']) ? $meta['alias'] : $attributePath;
        unset($meta['alias']);

        // TODO ne pas gerer les defaults
        $meta += [
            'primary'    => null,
            'default'    => null,
            'phpOptions' => [],
        ];

        $this->fields[$field] = $this->attributes[$attributePath] = [
            'field'             => $field,
            'attribute'         => $attributePath,
            'embedded'          => $path,
        ] + $meta;
        
        /*
         * Construction des meta des primary keys.
         * Si un champs est en auto-increment, celui-ci doit etre placé en début de tableau.
         */
        if ($meta['primary']) {
            if ($this->primary['type'] !== self::PK_AUTO && $meta['primary'] !== self::PK_AUTO) {
                throw new \LogicException('Trying to set a primary key');
            }
            
            if ($meta['primary'] === self::PK_AUTO) {
                $this->primary['attributes'][] = $attributePath;
                $this->primary['fields'][]     = $field;
            } else {
                $this->primary['type'] = $meta['primary'];
                array_unshift($this->primary['attributes'], $attributePath);
                array_unshift($this->primary['fields'], $field);
            }
        }
    }
    
    /**
     * Build sequence info if primary is a sequence
     * 
     * @param array $sequence
     */
    private function buildSequence($sequence)
    {
        if (!$this->isSequencePrimaryKey()) {
            return;
        }
        
        $this->sequence = [
            'connection' => isset($sequence['connection'])
                            ? $sequence['connection']
                            : $this->connection,
            'table'      => isset($sequence['table'])
                            ? $sequence['table']
                            : $this->table . '_seq',
            'column'     => isset($sequence['column'])
                            ? $sequence['column']
                            : 'id',
            'options'    => $sequence['tableOptions'],
        ];
    }
}
