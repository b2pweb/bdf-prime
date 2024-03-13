<?php

namespace Bdf\Prime;

use Bdf\Prime\Connection\Configuration\ConfigurationResolver;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\ConnectionRegistry;
use Bdf\Prime\Connection\Factory\ChainFactory;
use Bdf\Prime\Connection\Factory\ConnectionFactory;
use Bdf\Prime\Connection\Factory\MasterSlaveConnectionFactory;
use Bdf\Prime\Connection\Factory\ShardingConnectionFactory;
use Bdf\Prime\Connection\Middleware\LoggerMiddleware;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Mapper\MapperFactory;
use Bdf\Prime\Repository\RepositoryInterface;
use Doctrine\DBAL\Logging\SQLLogger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Prime
 *
 * Usefull facade to manipulate repositories
 * Allow user to create, drop, truncate repositories
 * Allow user to find, insert entities
 */
class Prime
{
    /**
     * @var mixed
     */
    protected static $config;

    /**
     * @var ServiceLocator
     */
    protected static $serviceLocator;


    /**
     * Configure the locator
     *
     * @param array|ContainerInterface|ServiceLocator $config
     *
     * @return void
     */
    public static function configure($config): void
    {
        if ($config instanceof ServiceLocator) {
            static::$config = null;
            static::$serviceLocator = $config;
        } else {
            static::$config = $config;
            static::$serviceLocator = null;
        }
    }

    /**
     * Check whether prime is configured
     *
     * @return bool
     */
    public static function isConfigured()
    {
        return static::$config !== null;
    }

    //
    //--------- repository
    //

    /**
     * Get a repository
     *
     * @param class-string<T>|RepositoryInterface<T>|T $repository
     *
     * @return RepositoryInterface<T>|null
     *
     * @template T as object
     */
    public static function repository($repository)
    {
        if ($repository instanceof RepositoryInterface) {
            /** @var RepositoryInterface<T> $repository */
            return $repository;
        }

        return static::service()->repository($repository);
    }

    /**
     * Create repositories
     *
     * @param string|array|RepositoryInterface $repositories
     * @param boolean                          $force @see EntityRepository::schema
     *
     * @throws PrimeException
     *
     * @return void
     */
    public static function create($repositories, $force = false): void
    {
        static::callSchemaResolverMethod('migrate', $repositories, $force);
    }

    /**
     * Drop repositories
     *
     * @param string|array|RepositoryInterface $repositories
     * @param boolean                          $force @see EntityRepository::schema
     *
     * @throws PrimeException
     *
     * @return void
     */
    public static function drop($repositories, $force = false): void
    {
        static::callSchemaResolverMethod('drop', $repositories, $force);
    }

    /**
     * Truncate repositories
     *
     * @param string|array|RepositoryInterface $repositories
     * @param boolean                          $force @see EntityRepository::schema
     *
     * @throws PrimeException
     *
     * @return void
     */
    public static function truncate($repositories, $force = false): void
    {
        static::callSchemaResolverMethod('truncate', $repositories, $force);
    }

    /**
     * Call schema resolver method
     *
     * @param string  $method
     * @param mixed   $repositories
     * @param boolean $force
     *
     * @throws PrimeException
     *
     * @return void
     */
    protected static function callSchemaResolverMethod($method, $repositories, $force): void
    {
        if (!is_array($repositories)) {
            $repositories = [$repositories];
        }

        foreach ($repositories as $repository) {
            static::repository($repository)->schema($force)->$method();
        }
    }

    //
    //--------- entities
    //

    /**
     * Push multiple entities in repository
     * launch replace method from repository
     *
     * User can add
     *  * entity object
     *  * collection of entity object
     *  * an array of entity attributes
     *
     * <code>
     *  Prime::push(new EntityClass());
     *  Prime::push([new EntityClass()]);
     *  Prime::push($repository, ['id' => '...']);
     *  Prime::push('EntityClass', ['id' => '...']);
     *  Prime::push('EntityClass', [['id' => '...']]);
     * </code>
     *
     * @param mixed $repositoryName
     * @param mixed $entities
     *
     * @throws PrimeException
     *
     * @return void
     */
    public static function push($repositoryName, $entities = null): void
    {
        static::callRepositoryMethod('replace', $repositoryName, $entities);
    }

    /**
     * Save multiple entities in repository
     * launch save method from repository
     *
     * User can add
     *  * entity object
     *  * collection of entity object
     *  * an array of entity attributes
     *
     * <code>
     *  Prime::save(new EntityClass());
     *  Prime::save([new EntityClass()]);
     *  Prime::save($repository, ['id' => '...']);
     *  Prime::save('EntityClass', ['id' => '...']);
     *  Prime::save('EntityClass', [['id' => '...']]);
     * </code>
     *
     * @param mixed $repositoryName
     * @param mixed $entities
     *
     * @throws PrimeException
     *
     * @return void
     */
    public static function save($repositoryName, $entities = null): void
    {
        static::callRepositoryMethod('save', $repositoryName, $entities);
    }

    /**
     * Remove multiple entities in repository
     *
     * User can add
     *  * entity object
     *  * collection of entity object
     *  * an array of entity attributes
     *
     * <code>
     *  Prime::remove(new EntityClass());
     *  Prime::remove([new EntityClass()]);
     *  Prime::remove($repository, ['id' => '...']);
     *  Prime::remove('EntityClass', ['id' => '...']);
     *  Prime::remove('EntityClass', [['id' => '...']]);
     * </code>
     *
     * @param mixed $repositoryName
     * @param mixed $entities
     *
     * @throws PrimeException
     *
     * @return void
     */
    public static function remove($repositoryName, $entities = null): void
    {
        static::callRepositoryMethod('delete', $repositoryName, $entities);
    }

    /**
     * Call repository method for entities
     *
     * @param string $method
     * @param mixed $repositoryName
     * @param mixed $entities
     *
     * @throws PrimeException
     *
     * @return void
     */
    protected static function callRepositoryMethod($method, $repositoryName, $entities): void
    {
        if (!is_string($repositoryName) && !$repositoryName instanceof RepositoryInterface) {
            $entities = $repositoryName;
            $repositoryName = null;
        }

        if (!is_array($entities) || !isset($entities[0])) {
            $entities = [$entities];
        }

        foreach ($entities as $entity) {
            $repository = static::repository($repositoryName ?: $entity);

            if (is_array($entity)) {
                $entity = $repository->entity($entity);
            }

            $repository->$method($entity);
        }
    }

    /**
     * Assert that entity exists
     *
     * @param object $entity
     * @param bool   $compare  Will compare entity with the expected one
     *
     * @return bool
     *
     * @throws PrimeException
     */
    public static function exists($entity, $compare = true)
    {
        $repository = static::repository($entity);

        $expected = $repository->refresh($entity);

        if ($expected === null) {
            return false;
        }

        if (!$compare) {
            return true;
        }

        return $entity == $expected
                ? true
                : serialize($entity) === serialize($expected);
    }

    /**
     * Find entity
     *
     * <code>
     *  Prime::find(new EntityClass());
     *  Prime::find('EntityClass', ['id' => '...']);
     *  Prime::find($repository, ['id' => '...']);
     * </code>
     *
     * @param class-string<T>|RepositoryInterface<T>|T $repositoryName Repo name or Entity instance
     * @param array|object|null $criteria Array of criteria. Optional if repository name is an object
     *
     * @return T[]|\Bdf\Prime\Collection\CollectionInterface<T>
     *
     * @throws PrimeException
     *
     * @template T as object
     */
    public static function find($repositoryName, $criteria = null)
    {
        /** @psalm-suppress InvalidArgument */
        $repository = static::repository($repositoryName);

        // if $repositoryName is an entity
        if (is_object($repositoryName) && !$repositoryName instanceof RepositoryInterface) {
            $criteria = $repository->mapper()->prepareToRepository($repositoryName);
        }

        return $repository->queries()->builder()->where($criteria)->all();
    }

    /**
     * Find one entity
     *
     * <code>
     *  Prime::one(new EntityClass());
     *  Prime::one('EntityClass', ['id' => '...']);
     *  Prime::one($repository, ['id' => '...']);
     * </code>
     *
     * @param class-string<T>|RepositoryInterface<T>|T $repositoryName Repo name or Entity instance
     * @param array|object|null $criteria Array of criteria. Optional if repository name is an object
     *
     * @return T|null
     *
     * @throws PrimeException
     *
     * @template T as object
     */
    public static function one($repositoryName, $criteria = null)
    {
        /** @psalm-suppress InvalidArgument */
        $repository = static::repository($repositoryName);

        // if $repositoryName is an entity
        if (is_object($repositoryName) && !$repositoryName instanceof RepositoryInterface) {
            $criteria = $repository->mapper()->prepareToRepository($repositoryName);
        }

        return $repository->queries()->builder()->where($criteria)->first();
    }

    /**
     * Refresh entity from repository
     *
     * @param T $entity
     * @param array $additionalCriteria  Criteria to add to primary key
     *
     * @return T|null New refresh entity
     *
     * @throws PrimeException
     *
     * @template T as object
     */
    public static function refresh($entity, $additionalCriteria = [])
    {
        $repository = static::repository($entity);

        return $repository->refresh($entity, $additionalCriteria);
    }

    //
    //--------- service
    //

    /**
     * Get active connection from profile name
     *
     * @param string $name
     *
     * @return ConnectionInterface
     */
    public static function connection($name = null)
    {
        return static::service()->connection($name);
    }

    /**
     * Get service locator
     *
     * @return ServiceLocator
     */
    public static function service()
    {
        if (static::$serviceLocator === null) {
            static::initialize();
        }

        return static::$serviceLocator;
    }

    /**
     * Initializes the service locator
     *
     * @return void
     */
    protected static function initialize()
    {
        if (static::$config instanceof ContainerInterface) {
            static::$serviceLocator = static::$config->get('prime');

            return;
        }

        if (!is_array(static::$config)) {
            throw new RuntimeException('Prime is not configured');
        }

        $factory = new ConnectionFactory();
        $registry = new ConnectionRegistry(
            static::$config['connection']['config'] ?? [],
            new ChainFactory([
                new MasterSlaveConnectionFactory($factory),
                new ShardingConnectionFactory($factory),
                $factory,
            ]),
            new ConfigurationResolver([], $configuration = new Configuration())
        );

        $mapperFactory = new MapperFactory(
            null,
            static::$config['metadataCache'] ?? null,
            static::$config['resultCache'] ?? null
        );

        static::$serviceLocator = new ServiceLocator(
            new ConnectionManager($registry),
            $mapperFactory
        );

        if ($logger = static::$config['logger'] ?? null) {
            if ($logger instanceof SQLLogger) {
                /** @psalm-suppress DeprecatedMethod */
                $configuration->setSQLLogger($logger);
            } elseif ($logger instanceof LoggerInterface) {
                $configuration->setMiddlewares([new LoggerMiddleware($logger)]);
            }
        }

        if ($types = static::$config['types'] ?? null) {
            foreach ($types as $alias => $type) {
                $configuration->getTypes()->register($type, is_string($alias) ? $alias : null);
            }
        }

        if ($types = static::$config['platformTypes'] ?? null) {
            foreach ($types as $alias => $type) {
                $configuration->addPlatformType($type, is_string($alias) ? $alias : null);
            }
        }

        if ($serializer = static::$config['serializer'] ?? null) {
            static::$serviceLocator->setSerializer($serializer);
        }
    }
}
