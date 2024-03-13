<?php

namespace Bdf\Prime\Mapper;

use Bdf\Prime\Entity\Instantiator\InstantiatorInterface;
use Bdf\Prime\Mapper\Attribute\MapperConfigurationInterface;
use Bdf\Prime\Relations\Builder\RelationBuilder;
use Bdf\Prime\Relations\Relation;
use Bdf\Prime\ValueObject\ValueObjectInterface;
use LogicException;
use stdClass;

/**
 * Metadata
 *
 * @todo gerer le nom de la base de données si non fourni
 * @todo exception si aucune primary ou unique n'a été définit ?
 * @todo doit on injecter si private ??
 *
 * @todo Allow enum class on valueObject ?
 * @psalm-type FieldMetadata = array{
 *     primary: Metadata::PK_*|null,
 *     type: string,
 *     default: mixed,
 *     phpOptions: array<string, mixed>,
 *     field: string,
 *     attribute: string,
 *     embedded: string|null,
 *     length?: int,
 *     comment?: string,
 *     nillable?: bool,
 *     unsigned?: bool,
 *     unique?: bool|string,
 *     class?: class-string,
 *     valueObject?: class-string<ValueObjectInterface>,
 * }
 *
 * @psalm-type SequenceMetadata = array{
 *     connection: string|null,
 *     table: string|null,
 *     column: string|null,
 *     options: array
 * }
 *
 * @psalm-type EmbeddedMetadata = array{
 *     path: string,
 *     parentPath: string,
 *     paths: list<string>,
 *     class?: class-string,
 *     hint?: int|null,
 *     class_map?: array<string, class-string>,
 *     hints?: array<class-string, int|null>,
 *     discriminator_field?: string,
 *     discriminator_attribute?: string
 * }
 *
 * @psalm-type IndexMetadata = array{
 *     fields:array<string, array<string, string>>,
 *     unique?: bool
 * }&array<string, string>
 *
 * @psalm-import-type FieldDefinition from \Bdf\Prime\Mapper\Builder\FieldBuilder
 * @psalm-import-type RelationDefinition from RelationBuilder
 */
class Metadata
{
    /* constantes définissant le type de primary key */
    public const PK_AUTOINCREMENT = 'autoincrement';
    public const PK_AUTO = true;
    public const PK_SEQUENCE = 'sequence';

    /**
     * The expected entity classname
     *
     * @var class-string
     */
    public $entityName;

    /**
     * The instantiator hint
     *
     * @var int|null
     */
    public $instantiatorHint;

    /**
     * The class name to use
     *
     * if the class name does not exist, a stdClass will be used
     *
     * @var class-string
     */
    public $entityClass;

    /**
     * The property accessor class name to use
     * Usefull only for building metadata
     *
     * @var class-string
     */
    public $propertyAccessorClass;

    /**
     * @var string
     */
    public $connection;

    /**
     * @var string|null
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
     * @var array<string, string>
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
     * @var IndexMetadata[]
     */
    public $indexes = [];

    /**
     * @var SequenceMetadata
     */
    public $sequence = [
        'connection' => null,
        'table'      => null,
        'column'     => null,
        'options'    => [],
    ];

    /**
     * List of entity columns, indexed by the database columns name
     *
     * @var array<string, FieldMetadata>
     */
    public $fields = [];

    /**
     * List of entity columns, indexed by the entity property name
     *
     * @var array<string, FieldMetadata>
     */
    public $attributes = [];

    /**
     * @var array<string, EmbeddedMetadata>
     */
    public $embeddeds = [];

    /**
     * @var array{
     *     type: Metadata::PK_*,
     *     attributes: list<string>,
     *     fields: list<string>
     * }
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
     * List of mapper configurators
     *
     * @var array<MapperConfigurationInterface>
     */
    public array $configurators = [];

    /**
     * Get entity class name
     *
     * @return class-string
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    /**
     * Is metadata built
     *
     * @return bool
     */
    public function isBuilt(): bool
    {
        return $this->built;
    }

    /**
     * Get connection identifier from locator
     *
     * @return string|null
     */
    public function connection(): ?string
    {
        return $this->connection;
    }

    /**
     * Get database name
     *
     * @return string|null
     */
    public function database(): ?string
    {
        return $this->database;
    }

    /**
     * Get table name
     *
     * @return string
     */
    public function table(): string
    {
        return $this->table;
    }

    /**
     * Get table options
     *
     * @return array
     */
    public function tableOptions(): array
    {
        return $this->tableOptions;
    }

    /**
     * Get indexes
     *
     * @return array<string, IndexMetadata>
     */
    public function indexes(): array
    {
        return $this->indexes;
    }

    /**
     * Get embedded meta
     *
     * @return array<string, EmbeddedMetadata>
     */
    public function embeddeds(): array
    {
        return $this->embeddeds;
    }

    /**
     * Get attribute embedded meta
     *
     * @param string $attribute
     *
     * @return EmbeddedMetadata|null
     */
    public function embedded($attribute): ?array
    {
        return $this->embeddeds[$attribute] ?? null;
    }

    /**
     * Get attribute or field metadata
     *
     * @param string $key
     * @param string $type
     *
     * @return array|null
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
     * @param 'attributes'|'fields'|'type' $type
     *
     * @return list<string>|Metadata::PK_*
     */
    public function primary($type = 'attributes')
    {
        return $this->primary[$type];
    }

    /**
     * Returns metadata for first primary key
     *
     * @return FieldMetadata
     */
    public function firstPrimaryMeta(): array
    {
        list($primary) = $this->primary['attributes'];

        return $this->attributes[$primary];
    }

    /**
     * Returns all metadata for primary key
     *
     * @return array<string, FieldMetadata>
     */
    public function primaryMeta(): array
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
     * @param 'connection'|'table'|'column'|'options'|null $key
     *
     * @return SequenceMetadata|string|array|null
     */
    public function sequence(?string $key = null)
    {
        return $key === null
            ? $this->sequence
            : $this->sequence[$key];
    }

    /**
     * Check if key is primary
     *
     * @param string $key
     * @param 'attributes'|'fields' $type
     *
     * @return bool
     */
    public function isPrimary($key, $type = 'attributes'): bool
    {
        return in_array($key, $this->primary[$type]);
    }

    /**
     * Is primary key an auto increment
     *
     * @return bool
     */
    public function isAutoIncrementPrimaryKey(): bool
    {
        return $this->primary['type'] === self::PK_AUTOINCREMENT;
    }

    /**
     * Is a sequence generated primary key
     *
     * @return bool
     *
     * @psalm-assert string $this->sequence['table']
     * @psalm-assert string $this->sequence['column']
     */
    public function isSequencePrimaryKey(): bool
    {
        return $this->primary['type'] === self::PK_SEQUENCE;
    }

    /**
     * Is a foreign key as primary key
     *
     * @return bool
     */
    public function isForeignPrimaryKey(): bool
    {
        return $this->primary['type'] === self::PK_AUTO;
    }

    /**
     * The primary key has multiple fields
     *
     * @return bool
     */
    public function isCompositePrimaryKey(): bool
    {
        return count($this->primary['attributes']) > 1;
    }

    /**
     * Get fields metadata
     *
     * @return array<string, FieldMetadata>
     */
    public function fields(): array
    {
        return $this->fields;
    }

    public function eagerRelations(): array
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
    public function fieldExists($field): bool
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
    public function fieldType($field): string
    {
        return $this->fields[$field]['type'];
    }

    /**
     * Get attributes
     *
     * @return array<string, FieldMetadata>
     */
    public function attributes(): array
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
    public function attributeExists($attribute): bool
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
    public function attributeType($attribute): string
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
    public function fieldFrom($attribute): string
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
    public function attributeFrom($field): string
    {
        return $this->fields[$field]['attribute'];
    }

    /**
     * @param Mapper $mapper
     * @psalm-param Mapper<E> $mapper
     * @template E as object
     */
    public function build(Mapper $mapper): void
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
     * @param class-string $entityClass
     *
     * @return class-string
     */
    private function getExistingClassName($entityClass): string
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
     * @param class-string $className
     *
     * @return null|int
     */
    private function getInstantiatorHint($className): ?int
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
     * @param array{connection: string, database?: string, table: string, tableOptions?: array} $schema
     */
    private function buildSchema(array $schema): void
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
     * @param iterable<string, FieldDefinition> $fields
     * @param EmbeddedMetadata|null $embeddedMeta
     */
    private function buildFields(iterable $fields, $embeddedMeta = null): void
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
    private function buildIndexes(array $indexes): void
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
    private function buildSimpleIndexes(array $indexes): void
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
    private function buildIndexesFromFields(): void
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
     * @param class-string $class
     * @param array|null $embeddedMeta
     *
     * @return EmbeddedMetadata
     */
    private function buildEmbedded(string $attribute, string $class, ?array $embeddedMeta): array
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
     * @param array|null $embeddedMeta
     *
     * @return EmbeddedMetadata
     */
    private function buildPolymorph($attribute, array $meta, ?array $embeddedMeta): array
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
     * @param string[] $map
     * @param string $discriminator
     * @param array  $embeddedMeta
     */
    private function buildMappedEmbedded($attribute, $map, $discriminator, $embeddedMeta = null): void
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
     * @param iterable<string, RelationDefinition> $relations
     */
    private function buildRelations(iterable $relations): void
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
     * @param FieldDefinition $meta
     * @param EmbeddedMetadata|null $embeddedMeta
     */
    private function buildField($attribute, $meta, ?array $embeddedMeta): void
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

        if (isset($this->fields[$field])) {
            throw new LogicException('Alias "' . $field . '" is already in use. If you want to use the same database field on multiple properties, you can declare an event listener for the "afterLoad", "beforeInsert" and "beforeUpdate" events, using Mapper::customEvents() to manually set the value on all properties.');
        }

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
                throw new LogicException('Trying to set a primary key');
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
     * @param array{connection?:string,table?:string,column?:string,tableOptions?:array} $sequence
     */
    private function buildSequence($sequence): void
    {
        if (!$this->isSequencePrimaryKey()) {
            return;
        }

        $this->sequence = [
            'connection' => $sequence['connection'] ?? $this->connection,
            'table'      => $sequence['table'] ?? $this->table . '_seq',
            'column'     => $sequence['column'] ?? 'id',
            'options'    => $sequence['tableOptions'] ?? [],
        ];
    }
}
