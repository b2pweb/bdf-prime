<?php

namespace Bdf\Prime\Mapper;

use Bdf\Prime\Behaviors\BehaviorInterface;
use Bdf\Prime\Cache\CacheInterface;
use Bdf\Prime\Entity\Criteria;
use Bdf\Prime\Entity\Hydrator\MapperHydrator;
use Bdf\Prime\Entity\Hydrator\MapperHydratorInterface;
use Bdf\Prime\Entity\ImportableInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\IdGenerators\AutoIncrementGenerator;
use Bdf\Prime\IdGenerators\GeneratorInterface;
use Bdf\Prime\IdGenerators\NullGenerator;
use Bdf\Prime\IdGenerators\TableGenerator;
use Bdf\Prime\Mapper\Attribute\Filter;
use Bdf\Prime\Mapper\Attribute\MapperConfigurationInterface;
use Bdf\Prime\Mapper\Attribute\RepositoryMethod;
use Bdf\Prime\Mapper\Attribute\Scope;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Builder\IndexBuilder;
use Bdf\Prime\Mapper\Info\MapperInfo;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Relations\Builder\RelationBuilder;
use Bdf\Prime\Relations\Exceptions\RelationNotFoundException;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Prime\Repository\RepositoryEventsSubscriberInterface;
use Bdf\Prime\Repository\RepositoryInterface;
use Bdf\Prime\ServiceLocator;
use Bdf\Serializer\PropertyAccessor\PropertyAccessorInterface;
use Bdf\Serializer\PropertyAccessor\ReflectionAccessor;
use Closure;
use LogicException;
use ReflectionAttribute;
use ReflectionObject;
use stdClass;

use function class_exists;
use function is_string;
use function method_exists;

/**
 * Mapper
 *
 * Contient les méta données de la table.
 *
 * @todo Convertir la donnée avec le type approprié sur les methodes setId, hydrateOne
 *
 * @template E as object
 *
 * @psalm-import-type FieldDefinition from FieldBuilder
 * @psalm-import-type RelationDefinition from RelationBuilder
 */
abstract class Mapper
{
    /**
     * Enable/Disable query result cache on repository
     * If null global cache will be set.
     * Set it to false to deactivate cache on this repository
     * Set the cache instance in configure method
     *
     * @var false|CacheInterface
     */
    protected $resultCache;

    /**
     * @var Metadata|null
     */
    private ?Metadata $metadata;

    /**
     * Id generator
     *
     * Could be defined as string (generator class name). It would be instantiated
     * by mapper on generator() method
     *
     * @var GeneratorInterface|class-string<GeneratorInterface>|null
     */
    protected $generator;

    /**
     * @var class-string
     */
    private string $repositoryClass = EntityRepository::class;

    /**
     * The real name of entity class. Could be an none existing class
     *
     * @var class-string<E>
     */
    private string $entityClass;

    /**
     * The property accessor class name to use by default
     *
     * @var class-string<PropertyAccessorInterface>
     */
    private string $propertyAccessorClass = ReflectionAccessor::class;

    /**
     * Class of the criteria to use
     * If null, the class will be resolved from the entity class with the suffix "Criteria" if exists
     *
     * @var class-string<Criteria>|null
     */
    private ?string $criteriaClass = null;

    /**
     * Set repository read only.
     *
     * @var bool
     */
    private bool $readOnly = false;

    /**
     * Use schema resolver
     * Disable if schema has not to be manage by this app
     *
     * @var bool
     */
    private bool $useSchemaManager = true;

    /**
     * Use quote identifier
     * Allows query builder to use quote identifier
     *
     * @var bool
     */
    private bool $useQuoteIdentifier = false;

    /**
     * Allow usage of unknown attribute
     *
     * If true, query with undeclared attributes on criteria will not throw an exception , and use it as is
     * This is useful for example for the select compilation, where the attribute can be a DBAL expression,
     * but can produce security issue if input is not properly checked
     *
     * For compatibility reason, this value is null by default, which will raise a deprecated notice, and enable the feature.
     * In next major version, this value will be false by default, and should be manually enabled on Mapper.
     *
     * @var bool|null
     */
    private ?bool $allowUnknownAttribute = null;

    /**
     * The relation builder
     *
     * @var RelationBuilder
     */
    private ?RelationBuilder $relationBuilder = null;

    /**
     * The collection of behaviors
     *
     * @var BehaviorInterface<E>[]
     */
    private ?array $behaviors = null;

    /**
     * The service locator
     *
     * @var ServiceLocator
     */
    protected ServiceLocator $serviceLocator;

    /**
     * @var MapperHydratorInterface<E>|null
     */
    protected ?MapperHydratorInterface $hydrator;

    /**
     * @var array<string, callable>|null
     */
    private ?array $scopes = null;

    /**
     * @var array<string, callable>|null
     */
    private ?array $filters = null;

    /**
     * @var array<string, callable(\Bdf\Prime\Repository\RepositoryInterface<E>,mixed...):mixed>|null
     */
    private ?array $queries = null;


    /**
     * Mapper constructor
     *
     * @param ServiceLocator $serviceLocator
     * @param class-string<E>|null $entityClass
     * @param Metadata|null $metadata
     * @param MapperHydratorInterface<E>|null $hydrator
     * @param CacheInterface|null $resultCache
     */
    public function __construct(ServiceLocator $serviceLocator, ?string $entityClass = null, ?Metadata $metadata = null, ?MapperHydratorInterface $hydrator = null, ?CacheInterface $resultCache = null)
    {
        $this->entityClass = $entityClass ?? stdClass::class;
        $this->metadata = $metadata;
        $this->serviceLocator = $serviceLocator;
        $this->resultCache = $resultCache;
        $this->hydrator = $hydrator;
    }

    /**
     * Custom configuration
     */
    public function configure(): void
    {
        // to overwrite
    }

    /**
     * Get entity class
     *
     * @return class-string<E>
     * @final
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    /**
     * Get metadata
     *
     * @return Metadata
     * @final
     *
     * @psalm-suppress InvalidNullableReturnType
     * @psalm-suppress NullableReturnStatement
     */
    public function metadata(): Metadata
    {
        return $this->metadata;
    }

    /**
     * Set property accessor class name
     *
     * @param class-string<PropertyAccessorInterface> $className
     * @final
     */
    public function setPropertyAccessorClass(string $className): void
    {
        $this->propertyAccessorClass = $className;
    }

    /**
     * Get property accessor class name
     *
     * @return class-string<PropertyAccessorInterface>
     * @final
     */
    public function getPropertyAccessorClass(): string
    {
        return $this->propertyAccessorClass;
    }

    /**
     * Set repository class name
     *
     * @param class-string $className
     * @final
     */
    public function setRepositoryClass(string $className): void
    {
        $this->repositoryClass = $className;
    }

    /**
     * Get repository class name
     *
     * @return class-string
     * @final
     */
    public function getRepositoryClass(): string
    {
        return $this->repositoryClass;
    }

    /**
     * Set the repository read only
     *
     * @param bool $flag
     * @final
     */
    public function setReadOnly(bool $flag): void
    {
        $this->readOnly = $flag;
    }

    /**
     * Get repository read only state
     *
     * @return bool
     * @final
     */
    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    /**
     * Disable schema manager on repository
     * @final
     */
    public function disableSchemaManager(): void
    {
        $this->useSchemaManager = false;
    }

    /**
     * Does repository have a schema manager
     *
     * @return bool
     * @final
     */
    public function hasSchemaManager(): bool
    {
        return $this->useSchemaManager;
    }

    /**
     * Set the query builder quote identifier
     *
     * @param bool $flag
     * @final
     */
    public function setQuoteIdentifier(bool $flag): void
    {
        $this->useQuoteIdentifier = $flag;
    }

    /**
     * Does query builder use quote identifier
     *
     * @return bool
     * @final
     */
    public function hasQuoteIdentifier(): bool
    {
        return $this->useQuoteIdentifier;
    }

    /**
     * Allow usage of unknown attribute
     *
     * If true, query with undeclared attributes on criteria will not throw an exception , and use it as is
     * This is useful for example for the select compilation, where the attribute can be a DBAL expression,
     * but can produce security issue if input is not properly checked
     *
     * For compatibility reason, this value is null by default, which will raise a deprecated notice, and enable the feature.
     * In next major version, this value will be false by default, and should be manually enabled on Mapper.
     *
     * @return bool|null
     */
    final public function allowUnknownAttribute(): ?bool
    {
        return $this->allowUnknownAttribute;
    }

    /**
     * Allow or not usage of unknown attribute on query
     *
     * If true, query with undeclared attributes on criteria will not throw an exception , and use it as is
     * This is useful for example for the select compilation, where the attribute can be a DBAL expression,
     * but can produce security issue if input is not properly checked
     *
     * By default, this value is null, which will raise a deprecated notice, and enable the feature.
     * In next major version, this value will be false by default, and should be manually enabled.
     *
     * @param bool $allowUnknownAttribute
     */
    final public function setAllowUnknownAttribute(bool $allowUnknownAttribute): void
    {
        $this->allowUnknownAttribute = $allowUnknownAttribute;
    }

    /**
     * Set generator ID
     *
     * @param string|GeneratorInterface $generator
     * @final
     */
    public function setGenerator($generator): void
    {
        if (!is_string($generator) && !$generator instanceof GeneratorInterface) {
            throw new LogicException('Trying to set an invalid generator in "' . get_class($this) . '"');
        }

        $this->generator = $generator;
    }

    /**
     * Get generator ID
     *
     * @return GeneratorInterface
     * @final
     */
    public function generator(): GeneratorInterface
    {
        if ($this->generator === null) {
            if ($this->metadata->isAutoIncrementPrimaryKey()) {
                $this->generator = new AutoIncrementGenerator($this);
            } elseif ($this->metadata->isSequencePrimaryKey()) {
                $this->generator = new TableGenerator($this);
            } else {
                $this->generator = new NullGenerator();
            }
        } elseif (is_string($this->generator)) {
            $className = $this->generator;
            $this->generator = new $className($this);
        }

        return $this->generator;
    }

    /**
     * @return MapperHydratorInterface<E>
     * @final
     *
     * @psalm-suppress InvalidNullableReturnType
     * @psalm-suppress NullableReturnStatement
     */
    public function hydrator(): MapperHydratorInterface
    {
        return $this->hydrator;
    }

    /**
     * @param MapperHydratorInterface<E> $hydrator
     *
     * @return $this
     * @final
     */
    public function setHydrator(MapperHydratorInterface $hydrator)
    {
        $this->hydrator = $hydrator;
        $this->hydrator->setPrimeInstantiator($this->serviceLocator->instantiator());

        if ($this->metadata !== null) {
            $this->hydrator->setPrimeMetadata($this->metadata);
        }

        return $this;
    }

    /**
     * Define the criteria class
     * By default, it is the entity class with "Criteria" suffix if exists, else base Criteria class
     *
     * @param class-string<Criteria> $className
     */
    final public function setCriteriaClass(string $className): void
    {
        $this->criteriaClass = $className;
    }

    /**
     * Set ID value en entity
     * Only sequenceable attribute is set (the first one)
     *
     * @param E $entity
     * @param mixed $value
     *
     * @return void
     * @final
     */
    public function setId($entity, $value): void
    {
        $this->hydrateOne($entity, $this->metadata->primary['attributes'][0], $value);
    }

    /**
     * Get ID value of an entity
     * Only sequenceable attribute is get (the first one)
     *
     * @param E $entity
     *
     * @return mixed
     * @final
     */
    public function getId($entity)
    {
        return $this->extractOne($entity, $this->metadata->primary['attributes'][0]);
    }

    /**
     * Get attribute value of an entity
     *
     * @param E $entity
     * @param string $attribute
     *
     * @return mixed
     * @final
     */
    public function extractOne($entity, string $attribute)
    {
        return $this->hydrator->extractOne($entity, $attribute);
    }

    /**
     * Hydrate on property value of an entity
     *
     * @param E $entity
     * @param string $attribute
     * @param mixed  $value
     *
     * @return void
     * @final
     */
    public function hydrateOne($entity, string $attribute, $value): void
    {
        $this->hydrator->hydrateOne($entity, $attribute, $value);
    }

    /**
     * Get primary key criteria
     *
     * @param E $entity
     *
     * @return array
     * @final
     */
    public function primaryCriteria($entity): array
    {
        return $this->hydrator->flatExtract($entity, array_flip($this->metadata->primary['attributes']));
    }

    /**
     * Instanciate the related class entity
     *
     * @return E
     * @final
     */
    public function instantiate()
    {
        /** @var E */
        return $this->serviceLocator->instantiator()
            ->instantiate($this->metadata->entityClass, $this->metadata->instantiatorHint);
    }

    /**
     * User api to instantiate related entity
     *
     * @param array $data
     *
     * @return E
     * @final
     */
    public function entity(array $data)
    {
        $entity = $this->instantiate();

        // Allows custom import from developpers.
        if ($entity instanceof ImportableInterface) {
            $entity->import($data);
        } else {
            $this->serviceLocator->hydrator($this->metadata->entityClass)
                ->hydrate($entity, $data);
        }

        return $entity;
    }

    /**
     * Transform entity to db one dimension array
     *
     * @param E $entity Entity object
     * @param array|null $attributes  Attribute should be flipped as ['key' => true]
     *
     * @return array
     * @final
     */
    public function prepareToRepository($entity, ?array $attributes = null): array
    {
        return $this->hydrator->flatExtract($entity, $attributes);
    }

    /**
     * Get valid array for entity
     *
     * Inject one dimension array (db field) into entity
     * Map attribute and cast value
     *
     * $optimisation est un tableau donné par le query builder dans le but
     * d'optimiser le chargement des relations et des tableaux associatifs. Il contient les entités regroupés par
     * la valeur du champs demandé
     *
     * @param array             $data  Db data
     * @param PlatformInterface $platform
     *
     * @return E
     */
    public function prepareFromRepository(array $data, PlatformInterface $platform)
    {
        $entity = $this->instantiate();

        $this->hydrator->flatHydrate($entity, $data, $platform->types());

        return $entity;
    }

    /**
     * Get the repository
     *
     * @return RepositoryInterface<E>
     * @final
     */
    public function repository(): RepositoryInterface
    {
        $className = $this->repositoryClass;

        return new $className($this, $this->serviceLocator, $this->resultCache === false ? null : $this->resultCache);
    }

    /**
     * Create a criteria object for this entity
     *
     * @param array<string, mixed> $filters
     *
     * @return Criteria
     */
    public function criteria(array $filters = []): Criteria
    {
        $class = $this->criteriaClass;

        if (!$class) {
            $class = $this->entityClass . 'Criteria';
            $this->criteriaClass = $class = class_exists($class) ? $class : Criteria::class;
        }

        return new $class($filters);
    }

    /**
     * Get the mapper info
     *
     * @return MapperInfo
     * @throws PrimeException
     * @final
     */
    public function info(): MapperInfo
    {
        $platform = $this->serviceLocator->connection($this->metadata()->connection)->platform();

        return new MapperInfo($this, $platform->types());
    }

    /**
     * Get defined relation
     *
     * Build object relation defined by user
     *
     * @param string $relationName
     *
     * @return array  Metadata for relation definition
     *
     * @throws \RuntimeException  If relation or type does not exist
     */
    public function relation(string $relationName): array
    {
        $relations = $this->relations();

        if (!isset($relations[$relationName])) {
            throw new RelationNotFoundException('Relation "' . $relationName . '" is not set in ' . $this->metadata->entityName);
        }

        return $relations[$relationName];
    }

    //
    //------------ API configuration du mapping
    //

    /**
     * Definition du schema
     *
     * Definition
     *  - connection         : The connection name declare in connection manager (mandatory).
     *  - database           : The database name.
     *  - table              : The table name (mandatory).
     *  - tableOptions       : The table options (ex: engine => myisam).
     *
     * <code>
     *  return [
     *     'connection'   => (string),
     *     'database'     => (string),
     *     'table'        => (string),
     *     'tableOptions' => (array),
     *  ];
     * </code>
     *
     * @return array
     */
    abstract public function schema(): array;

    /**
     * Gets repository fields builder
     *
     * @return iterable<string, FieldDefinition>
     * @final
     *
     * @todo should be final
     */
    public function fields(): iterable
    {
        $builder = new FieldBuilder();
        $this->buildFields($builder);

        foreach ($this->behaviors() as $behavior) {
            $behavior->changeSchema($builder);
        }

        return $builder;
    }

    /**
     * Build fields from this mapper.
     *
     * To overwrite.
     *
     * @param FieldBuilder $builder
     */
    public function buildFields(FieldBuilder $builder): void
    {
        throw new LogicException('Fields must be defined in mapper '.__CLASS__);
    }

    /**
     * Sequence definition.
     *
     * The metadata will build the sequence info using this method if the primary key is defined as sequence (Metadata::PK_SEQUENCE).
     * Definition:
     *  - connection         : The connection name declare in connection manager. The table connection will be used by default.
     *  - table              : The table sequence name.
     *                         The table name with suffix '_seq' will be used by default.
     *  - column             : The sequence column name. Default 'id'.
     *  - tableOptions       : The sequence table options (ex: engine => myisam).
     *
     * <code>
     *  return [
     *     'connection'   => (string),
     *     'table'        => (string),
     *     'column'       => (string),
     *     'tableOptions' => (array),
     *  ];
     * </code>
     *
     * @return array{
     *     connection?: string|null,
     *     table?: string|null,
     *     column?: string|null,
     *     tableOptions?: array,
     * }
     */
    public function sequence(): array
    {
        return [
            'connection'   => null,
            'table'        => null,
            'column'       => null,
            'tableOptions' => [],
        ];
    }

    /**
     * Gets custom filters
     *
     * Returns additional filters for query
     * You can use PHP 8 attributes to mark methods as scopes using {@see Filter}
     * instead of overriding this method
     *
     * Note: you cannot mix PHP 8 attributes and overriding this method
     *
     * <code>
     * class MyMapper extends Mapper
     * {
     *     // Legacy way
     *     public function filters(): array
     *     {
     *         return [
     *            'customFilterName' => function(<Bdf\Prime\Query\QueryInterface> $query, <mixed> $value) {
     *                return <void>
     *            },
     *         ];
     *     }
     *
     *     // PHP 8 way
     *     #[Filter]
     *     public function customFilterName(QueryInterface $query, $test): void
     *     {
     *     }
     * }
     *
     * $repository->where('customFilterName', 'test');
     * </code>
     *
     * @return array<string, callable>
     */
    public function filters(): array
    {
        return $this->filters ??= $this->loadMethodsFromAttributes(Filter::class);
    }

    /**
     * Array of index
     *
     * <code>
     *  return [
     *      ['attribute1', 'attribute2']
     *  ];
     * </code>
     *
     * @return array
     * @final
     *
     * @todo Make final
     */
    public function indexes(): array
    {
        $builder = new IndexBuilder();

        $this->buildIndexes($builder);

        return $builder->build();
    }

    /**
     * Build the table indexes
     * Note: Indexes can be added on undeclared fields
     *
     * <code>
     * public function buildIndexes(IndexBuilder $builder)
     * {
     *     $builder
     *         ->add()->on('name')->unique()
     *         ->add()->on('reference', ['length' => 12])
     *         ->add()->on(['type', 'date'])
     * }
     * </code>
     *
     * @param IndexBuilder $builder
     */
    public function buildIndexes(IndexBuilder $builder): void
    {
    }

    /**
     * Repository extension
     *
     * Returns additional methods in repository or query
     * You can use PHP 8 attributes to mark methods as scopes using {@see Scope}
     * instead of overriding this method
     *
     * Note: you cannot mix PHP 8 attributes and overriding this method
     *
     * <code>
     * class MyMapper extends Mapper
     * {
     *     // Legacy way
     *     public function scopes(): array
     *     {
     *         return [
     *             'customMethod' => function($query, $test) {
     *
     *             },
     *         ];
     *     }
     *
     *     // PHP 8 way
     *     #[Scope]
     *     public function customMethod($query, $test) {
     *     }
     * }
     *
     * $repository->customMethod('test');
     * </code>
     *
     * @return array<string, callable>
     */
    public function scopes(): array
    {
        $scopes = ($this->scopes ??= $this->loadMethodsFromAttributes(Scope::class));

        if (!$scopes) {
            throw new LogicException('No scopes have been defined in "' . get_class($this) . '"');
        }

        return $this->scopes = $scopes;
    }

    /**
     * Get custom queries for repository
     *
     * A custom query works mostly like scopes, but with some differences :
     * - Cannot be called using a query (i.e. $query->where(...)->myScope())
     * - The function has responsibility of creating the query instance
     * - The first argument is the repository
     *
     *  You can use PHP 8 attributes to mark methods as scopes using {@see RepositoryMethod}
     *  instead of overriding this method
     *
     *  Note: you cannot mix PHP 8 attributes and overriding this method
     *
     * <code>
     * class MyMapper extends Mapper
     * {
     *     // Legacy way
     *     public function queries(): array
     *     {
     *         return [
     *             'findByCustom' => function (EntityRepository $repository, $search) {
     *                 return $repository->make(MyCustomQuery::class)->where('first', $search)->first();
     *             }
     *         ];
     *     }
     *
     *     // PHP 8 way
     *     #[RepositoryMethod]
     *     public function findByCustom(EntityRepository $repository, $search)
     *     {
     *        return $repository->make(MyCustomQuery::class)->where('first', $search)->first();
     *     }
     * }
     * </code>
     *
     * @return array<string, callable(\Bdf\Prime\Repository\RepositoryInterface<E>,mixed...):mixed>
     */
    public function queries(): array
    {
        return $this->queries ??= $this->loadMethodsFromAttributes(RepositoryMethod::class);
    }

    /**
     * Register event on notifier
     *
     * @param RepositoryEventsSubscriberInterface<E> $notifier
     * @final
     */
    public function events(RepositoryEventsSubscriberInterface $notifier): void
    {
        $this->customEvents($notifier);

        foreach ($this->behaviors() as $behavior) {
            $behavior->subscribe($notifier);
        }
    }

    /**
     * Register custom event on notifier
     *
     * To overwrite.
     *
     * @param RepositoryEventsSubscriberInterface<E> $notifier
     */
    public function customEvents(RepositoryEventsSubscriberInterface $notifier): void
    {
        // To overwrite
    }

    /**
     * Get all behaviors
     *
     * @return BehaviorInterface<E>[]
     */
    final public function behaviors(): array
    {
        return $this->behaviors ??= $this->getDefinedBehaviors();
    }

    /**
     * Custom definition of behaviors
     *
     * To overwrite.
     *
     * @return BehaviorInterface<E>[]
     */
    public function getDefinedBehaviors(): array
    {
        return [];
    }

    /**
     * Get all relations
     *
     * @return array<string, RelationDefinition>
     * @final
     *
     * @todo should be final
     */
    public function relations(): array
    {
        if ($this->relationBuilder === null) {
            $this->relationBuilder = new RelationBuilder();
            $this->buildRelations($this->relationBuilder);
        }

        return $this->relationBuilder->relations();
    }

    /**
     * Build relations from this mapper.
     *
     * To overwrite.
     *
     * @param RelationBuilder $builder
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        // to overwrite
    }

    /**
     * Get all constraints
     *
     * @return array
     */
    final public function constraints(): array
    {
        $constraints = $this->customConstraints();

        foreach ($this->behaviors() as $behavior) {
            $constraints += $behavior->constraints();
        }

        return $constraints;
    }

    /**
     * Register custom event on notifier
     *
     * To overwrite.
     *
     * <code>
     * return [
     *     'attribute' => 'value'
     * ]
     * </code>
     *
     * @return array
     */
    public function customConstraints(): array
    {
        return [];
    }

    /**
     * Clear dependencies for break cyclic references
     *
     * @internal
     */
    public function destroy(): void
    {
        unset($this->serviceLocator);
        unset($this->generator);
        unset($this->hydrator);
        unset($this->metadata);
    }

    /**
     * @internal
     */
    public function setResultCache(CacheInterface $resultCache): void
    {
        $this->resultCache = $resultCache;
    }

    /**
     * @internal
     */
    public function setMetadata(Metadata $metadata): void
    {
        $this->metadata = $metadata;
    }

    /**
     * @param class-string<E> $entityClass
     * @internal
     */
    public function setEntityClass(string $entityClass): void
    {
        $this->entityClass = $entityClass;
    }

    /**
     * Must be called after constructor and setters
     *
     * @internal
     */
    public function build(): void
    {
        $metadata = $this->metadata;

        if (!$metadata) {
            $metadata = $this->metadata = new Metadata();
            $metadata->configurators = $this->loadConfigurators();
        }

        foreach ($metadata->configurators as $configurator) {
            $configurator->configure($this);
        }

        $this->configure();

        $metadata->build($this);

        $this->hydrator ??= new MapperHydrator();
        $this->hydrator->setPrimeMetadata($metadata);
        $this->hydrator->setPrimeInstantiator($this->serviceLocator->instantiator());
    }

    /**
     * Loads the mapper configurators from attributes
     *
     * @return MapperConfigurationInterface[]
     */
    private function loadConfigurators(): array
    {
        if (!method_exists(ReflectionObject::class, 'getAttributes')) {
            return [];
        }

        $attributes = (new ReflectionObject($this))->getAttributes(MapperConfigurationInterface::class, ReflectionAttribute::IS_INSTANCEOF);
        $configurators = [];

        foreach ($attributes as $attribute) {
            $configurators[] = $attribute->newInstance();
        }

        return $configurators;
    }

    /**
     * Loads methods marked with given attribute, and store them in an array with method name as key
     *
     * @param class-string<Scope>|class-string<RepositoryMethod>|class-string<Filter> $attributeClass
     * @return array<string, callable>
     */
    private function loadMethodsFromAttributes(string $attributeClass): array
    {
        if (!method_exists(ReflectionObject::class, 'getAttributes')) {
            return [];
        }

        $functions = [];

        foreach ((new ReflectionObject($this))->getMethods() as $method) {
            foreach ($method->getAttributes($attributeClass) as $attribute) {
                $query = $attribute->newInstance();
                $name = $query->name() ?? $method->getName();

                if ($method->isPrivate()) {
                    throw new LogicException('The method "' . static::class . '::' . $method->getName() . '" must be public or protected to be used with attribute ' . $attributeClass);
                }

                if ($method->isPublic()) {
                    $functions[$name] = [$this, $method->getName()];
                } else {
                    $functions[$name] = Closure::fromCallable([$this, $method->getName()]);
                }
            }
        }

        return $functions;
    }
}
