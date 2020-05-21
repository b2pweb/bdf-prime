<?php

namespace Bdf\Prime\Mapper;

use Bdf\Prime\Behaviors\BehaviorInterface;
use Bdf\Prime\Entity\ImportableInterface;
use Bdf\Prime\Entity\Hydrator\MapperHydrator;
use Bdf\Prime\Entity\Hydrator\MapperHydratorInterface;
use Bdf\Prime\Mapper\Builder\IndexBuilder;
use Bdf\Prime\Mapper\Info\MapperInfo;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Relations\Exceptions\RelationNotFoundException;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Prime\ServiceLocator;
use Bdf\Prime\IdGenerators\GeneratorInterface;
use Bdf\Prime\IdGenerators\AutoIncrementGenerator;
use Bdf\Prime\IdGenerators\NullGenerator;
use Bdf\Prime\IdGenerators\TableGenerator;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Relations\Builder\RelationBuilder;
use Bdf\Serializer\PropertyAccessor\ReflectionAccessor;
use LogicException;

/**
 * Mapper
 * 
 * Contient les méta données de la table.
 * 
 * @todo Convertir la donnée avec le type approprié sur les methodes setId, hydrateOne
 *
 * @package Bdf\Prime\Mapper
 */
abstract class Mapper
{
    /**
     * Enable/Disable query result cache on repository
     * If null global cache will be set.
     * Set it to false to deactivate cache on this repository
     * Set the cache class name in configure method
     * 
     * @var string|bool
     */
    protected $resultCacheClass;
    
    /**
     * @var Metadata
     */
    private $metadata;
    
    /**
     * Id generator
     * 
     * Could be defined as string (generator class name). It would be instantiated
     * by mapper on generator() method
     * 
     * @var \Bdf\Prime\IdGenerators\GeneratorInterface
     */
    protected $generator;
    
    /**
     * @var string
     */
    private $repositoryClass = EntityRepository::class;
    
    /**
     * The real name of entity class. Could be an none existing class
     * 
     * @var string
     */
    private $entityClass;

    /**
     * The property accessor class name to use by default
     *
     * @var string
     */
    private $propertyAccessorClass = ReflectionAccessor::class;

    /**
     * Set repository read only.
     * 
     * @var bool
     */
    private $readOnly = false;
    
    /**
     * Use schema resolver
     * Disable if schema has not to be manage by this app
     * 
     * @var bool
     */
    private $useSchemaManager = true;

    /**
     * Use quote identifier
     * Allows query builder to use quote identifier
     *
     * @var bool
     */
    private $useQuoteIdentifier = false;

    /**
     * The relation builder
     *
     * @var RelationBuilder
     */
    private $relationBuilder;

    /**
     * The collection of behaviors
     *
     * @var BehaviorInterface[]
     */
    private $behaviors;

    /**
     * The service locator
     *
     * @var ServiceLocator
     */
    protected $serviceLocator;

    /**
     * @var MapperHydratorInterface
     */
    protected $hydrator;


    /**
     * Mapper constructor
     *
     * @param ServiceLocator $serviceLocator
     * @param string $entityClass
     * @param Metadata $metadata
     * @param MapperHydratorInterface $hydrator
     */
    public function __construct(ServiceLocator $serviceLocator, $entityClass, $metadata = null, MapperHydratorInterface $hydrator = null)
    {
        $this->entityClass = $entityClass;
        $this->metadata = $metadata ?: new Metadata();
        $this->serviceLocator = $serviceLocator;

        $this->configure();
        
        $this->metadata->build($this);

        $this->setHydrator($hydrator ?: new MapperHydrator());
    }
    
    /**
     * Custom configuration
     */
    public function configure()
    {
        // to overwrite
    }

    /**
     * Get entity class
     * 
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }
    
    /**
     * Get metadata
     * 
     * @return Metadata
     */
    public function metadata()
    {
        return $this->metadata;
    }
    
    /**
     * Set property accessor class name
     * 
     * @param string $className
     */
    public function setPropertyAccessorClass($className)
    {
        $this->propertyAccessorClass = $className;
    }
    
    /**
     * Get property accessor class name
     * 
     * @return string
     */
    public function getPropertyAccessorClass()
    {
        return $this->propertyAccessorClass;
    }

    /**
     * Set repository class name
     *
     * @param string $className
     */
    public function setRepositoryClass($className)
    {
        $this->repositoryClass = $className;
    }

    /**
     * Get repository class name
     *
     * @return string
     */
    public function getRepositoryClass()
    {
        return $this->repositoryClass;
    }

    /**
     * Set the repository read only
     * 
     * @param bool $flag
     */
    public function setReadOnly($flag)
    {
        $this->readOnly = (bool)$flag;
    }
    
    /**
     * Get repository read only state
     * 
     * @return bool
     */
    public function isReadOnly()
    {
        return $this->readOnly;
    }
    
    /**
     * Disable schema manager on repository
     */
    public function disableSchemaManager()
    {
        $this->useSchemaManager = false;
    }
    
    /**
     * Does repository have a schema manager
     * 
     * @return bool
     */
    public function hasSchemaManager()
    {
        return $this->useSchemaManager;
    }

    /**
     * Set the query builder quote identifier
     *
     * @param bool $flag
     */
    public function setQuoteIdentifier($flag)
    {
        $this->useQuoteIdentifier = (bool)$flag;
    }

    /**
     * Does query builder use quote identifier
     *
     * @return bool
     */
    public function hasQuoteIdentifier()
    {
        return $this->useQuoteIdentifier;
    }

    /**
     * Set generator ID
     * 
     * @param string|GeneratorInterface $generator
     */
    public function setGenerator($generator)
    {
        if (!is_string($generator) && !$generator instanceof GeneratorInterface) {
            throw new LogicException('Trying to set an invalid generator in "' . get_class($this) . '"');
        }
        
        $this->generator = $generator;
    }
    
    /**
     * Get generator ID
     * 
     * @return object
     */
    public function generator()
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
     * @return MapperHydratorInterface
     */
    public function hydrator()
    {
        return $this->hydrator;
    }

    /**
     * @param MapperHydratorInterface $hydrator
     *
     * @return $this
     */
    public function setHydrator(MapperHydratorInterface $hydrator)
    {
        $this->hydrator = $hydrator;
        $this->hydrator->setPrimeInstantiator($this->serviceLocator->instantiator());
        $this->hydrator->setPrimeMetadata($this->metadata);

        return $this;
    }

    /**
     * Set ID value en entity
     * Only sequenceable attribute is set (the first one)
     *
     * @param object $entity
     * @param mixed $value
     */
    public function setId($entity, $value)
    {
        $this->hydrateOne($entity, $this->metadata->primary['attributes'][0], $value);
    }

    /**
     * Get ID value of an entity
     * Only sequenceable attribute is get (the first one)
     * 
     * @param object $entity
     *
     * @return mixed
     */
    public function getId($entity)
    {
        return $this->extractOne($entity, $this->metadata->primary['attributes'][0]);
    }
    
    /**
     * Get attribute value of an entity
     * 
     * @param object $entity
     * @param string $attribute
     *
     * @return mixed
     */
    public function extractOne($entity, $attribute)
    {
        return $this->hydrator->extractOne($entity, $attribute);
    }
    
    /**
     * Hydrate on property value of an entity
     * 
     * @param object $entity
     * @param string $attribute
     * @param mixed  $value
     */
    public function hydrateOne($entity, $attribute, $value)
    {
        $this->hydrator->hydrateOne($entity, $attribute, $value);
    }
    
    /**
     * Get primary key criteria
     * 
     * @param object $entity
     *
     * @return array
     */
    public function primaryCriteria($entity)
    {
        return $this->hydrator->flatExtract($entity, array_flip($this->metadata->primary['attributes']));
    }

    /**
     * Instanciate the related class entity
     *
     * @return object
     */
    public function instantiate()
    {
        return $this->serviceLocator->instantiator()
            ->instantiate($this->metadata->entityClass, $this->metadata->instantiatorHint);
    }

    /**
     * User api to instantiate related entity
     * 
     * @param array $data
     *
     * @return object
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
     * @param object $entity      Entity object
     * @param array  $attributes  Attribute should be flipped as ['key' => true]
     *
     * @return array
     *
     * @throws \Exception
     */
    public function prepareToRepository($entity, array $attributes = null)
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
     * @return object
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
     * @return \Bdf\Prime\Repository\RepositoryInterface
     */
    public function repository()
    {
        $className = $this->repositoryClass;
        
        if ($this->resultCacheClass === null) {
            $cache = $this->serviceLocator->config()->getResultCache();
        } elseif ($this->resultCacheClass === false) {
            $cache = null;
        } else {
            $cacheClassName = $this->resultCacheClass;
            $cache = new $cacheClassName();
        }
        
        return new $className($this, $this->serviceLocator, $cache);
    }

    /**
     * Get the mapper info
     *
     * @return MapperInfo
     */
    public function info()
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
    public function relation($relationName)
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
     * @return array|null
     */
    abstract public function schema();
    
    /**
     * Gets repository fields builder
     * 
     * @return FieldBuilder
     *
     * @todo should be final
     */
    public function fields()
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
    public function buildFields($builder)
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
     * @return array
     */
    public function sequence()
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
     * To overwrite
     * 
     * <code>
     *  return [
     *      'customFilterName' => function(<Bdf\Prime\Query\QueryInterface> $query, <mixed> $value) {
     *          return <void>
     *      },
     *  ];
     * </code>
     * 
     * @return array
     */
    public function filters()
    {
        return [];
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
     *
     * @todo Make final
     */
    public function indexes()
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
    public function buildIndexes(IndexBuilder $builder)
    {

    }
    
    /**
     * Repository extension
     * returns additionnals methods in repository
     * 
     * <code>
     * return [
     *     'customMethod' => function($query, $test) {
     *         
     *     },
     * ];
     * 
     * $repository->customMethod('test');
     * </code>
     * @return array
     */
    public function scopes()
    {
        throw new LogicException('No scopes have been defined in "' . get_class($this) . '"');
    }

    /**
     * Get custom queries for repository
     * A custom query works mostly like scopes, but with some differences :
     * - Cannot be called using a query (i.e. $query->where(...)->myScope())
     * - The function has responsability of creating the query instance
     * - The first argument is the repository
     *
     * <code>
     * return [
     *     'findByCustom' => function (EntityRepository $repository, $search) {
     *         return $repository->make(MyCustomQuery::class)->where('first', $search)->first();
     *     }
     * ];
     * </code>
     *
     * @return callable[]
     */
    public function queries()
    {
        return [];
    }
    
    /**
     * Register event on notifier
     * 
     * @param \Bdf\Event\EventNotifier $notifier
     */
    public function events($notifier)
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
     * @param \Bdf\Event\EventNotifier $notifier
     */
    public function customEvents($notifier)
    {
        // To overwrite
    }

    /**
     * Get all behaviors
     *
     * @return BehaviorInterface[]
     */
    final public function behaviors()
    {
        if ($this->behaviors === null) {
            $this->behaviors = $this->getDefinedBehaviors();
        }

        return $this->behaviors;
    }

    /**
     * Custom definition of behaviors
     *
     * To overwrite.
     *
     * @return BehaviorInterface[]
     */
    public function getDefinedBehaviors()
    {
        return [];
    }

    /**
     * Get all relations
     *
     * @return RelationBuilder
     *
     * @todo should be final
     */
    public function relations()
    {
        if ($this->relationBuilder === null) {
            $this->relationBuilder = new RelationBuilder();
            $this->buildRelations($this->relationBuilder);
        }

        return $this->relationBuilder;
    }

    /**
     * Build relations from this mapper.
     *
     * To overwrite.
     *
     * @param RelationBuilder $builder
     */
    public function buildRelations($builder)
    {
        // to overwrite
    }

    /**
     * Get all constraints
     * 
     * @return array
     */
    final public function constraints()
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
    public function customConstraints()
    {
        return [];
    }

    /**
     * Clear dependencies for break cyclic references
     *
     * @internal
     */
    public function destroy()
    {
        $this->serviceLocator = null;
        $this->generator = null;
        $this->hydrator = null;
        $this->metadata = null;
    }
}
