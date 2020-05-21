<?php

namespace Bdf\Prime;

use Bdf\Console\Console;
use Bdf\Prime\Console\CacheCommand;
use Bdf\Prime\Console\CreateDatabaseCommand;
use Bdf\Prime\Console\DropDatabaseCommand;
use Bdf\Prime\Console\EntityCommand;
use Bdf\Prime\Console\GraphCommand;
use Bdf\Prime\Console\HydratorGenerationCommand;
use Bdf\Prime\Console\MapperCommand;
use Bdf\Prime\Console\UpgraderCommand;
use Bdf\Prime\Entity\Instantiator\RegistryInstantiator;
use Bdf\Prime\Logger\PsrDecorator;
use Bdf\Prime\Mapper\MapperFactory;
use Bdf\Prime\Mapper\NameResolver\SuffixResolver;
use Bdf\Prime\Migration\Console\CheckCommand;
use Bdf\Prime\Migration\Console\DownCommand;
use Bdf\Prime\Migration\Console\GenerateCommand;
use Bdf\Prime\Migration\Console\InitCommand;
use Bdf\Prime\Migration\Console\MigrateCommand;
use Bdf\Prime\Migration\Console\RedoCommand;
use Bdf\Prime\Migration\Console\RollbackCommand;
use Bdf\Prime\Migration\Console\StatusCommand;
use Bdf\Prime\Migration\Console\UpCommand;
use Bdf\Prime\Migration\MigrationManager;
use Bdf\Prime\Migration\MigrationFactoryInterface;
use Bdf\Prime\Migration\MigrationProviderInterface;
use Bdf\Prime\Migration\Provider\FileMigrationProvider;
use Bdf\Prime\Migration\Provider\MigrationFactory;
use Bdf\Prime\Migration\Version\DbVersionRepository;
use Bdf\Prime\Migration\VersionRepositoryInterface;
use Bdf\Prime\Serializer\PaginatorNormalizer;
use Bdf\Prime\Serializer\PrimeCollectionNormalizer;
use Bdf\Prime\Types\ArrayObjectType;
use Bdf\Prime\Types\ArrayType;
use Bdf\Prime\Types\BooleanType;
use Bdf\Prime\Types\DateTimeType;
use Bdf\Prime\Types\DateType;
use Bdf\Prime\Types\JsonType;
use Bdf\Prime\Types\ObjectType;
use Bdf\Prime\Types\TimestampType;
use Bdf\Prime\Types\TimeType;
use Bdf\Prime\Types\TypeInterface;
use Bdf\Prime\Types\TypesRegistry;
use Bdf\Serializer\SerializerBuilder;
use Bdf\Serializer\SerializerInterface;
use Bdf\Web\Application;
use Bdf\Web\Providers\BootableProviderInterface;
use Bdf\Web\Providers\CommandProviderInterface;
use Bdf\Web\Providers\ServiceProviderInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;


/**
 * Configuration de prime
 * 
 * <pre>
 * Injecte dans le DI
 *   prime                   : (object) service locator
 *   prime-connectionManager : (object) connection manager
 *   prime-mapperFactory     : (object) mapper factory
 *   prime-instantiator      : (object) entity instantiator
 *   prime-logger            : (object) sql logger
 *   prime-migration-manager : (object) version manager for migration
 *   prime-migration-path    : (string) path to the migration files
 *   prime-types             : (array) custom prime types
 * 
 * Nécessite une configuration sous le format:
 *   prime.cache.result             = (string) result cache classname. Should implement \Bdf\Prime\Cache\CacheInterface
 *   prime.cache.metadata           = (string) metadata cache classname. Should implement \Psr\SimpleCache\CacheInterface
 *   prime.connection.default       = (string) default connection name. By default the first connection is  set as default
 *   prime.connection.config        = (string|array) config file. Contains all connections
 *   prime.connection.environment   = (string) config file environment
 *   prime.connection.log           = (bool) activate / deactivate sql log on application logger
 *   prime.facade                   = (bool) activate / deactivate facade
 *   prime.activerecord             = (bool) activate / deactivate active record pattern
 *   prime.migration.connection     = (string) Connection to use for migration table
 *   prime.migration.table          = (string) Db Table. Contains all version of migrations
 *   prime.migration.path           = (string) Migration file dir. Contains all migration classes
 *   prime.hydrators.loader         = (string) the file loads hydrators
 * </pre>
 * 
 * @package Bdf\Prime
 * 
 * @todo gerer dans la config le dsn et le tableau de profil
 * @todo gerer la configuration du mapper factory
 */
class PrimeServiceProvider implements ServiceProviderInterface, BootableProviderInterface, CommandProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(Application $app)
    {
        /**
         * ServiceLocator
         */
        $app->set('prime', function($app) {
            /** @var Application $app */

            $service = new ServiceLocator(
                $app->get('prime-connectionManager'),
                $app->get('prime-mapperFactory'),
                $app->get('prime-instantiator')
            );

            $service->setDI($app);
            $service->setSerializer($app->resolver('prime-serializer'));

            $loader = $app->config('prime.hydrators.loader');
            if ($loader && is_file($loader)) {
                $registry = $service->hydrators();
                include $loader;
            }

            $app->onReset(function() use($service) {
                $service->clearRepositories();
            });

            return $service;
        });
        
        /**
         * ConnectionManager
         */
        $app->set('prime-connectionManager', function($app) {
            /** @var Application $app */

            $config = $app->subConfig('prime');
            $connection = $config->chunk('connection');
            $cache = $config->chunk('cache');
            
            $connectionManager = new ConnectionManager();
            
            $connectionConfig = $connectionManager->config();
            $connectionConfig->setTypes($app->get('prime-types-registry'));
    
            if ($value = $connection->get('default')) {
                $connectionManager->setDefaultConnection($value);
            }

            if ($value = $connection->get('config')) {
                $connectionConfig->setDbConfig(function() use($app, $connection, $value) {
                    $environment = $connection->get('environment', defined('SERVER_DB_PROFILE') ? SERVER_DB_PROFILE : 'default');

                    return is_string($value)
                        ? $app->get('config.loader')->load($value, $environment)->all()
                        : (array)$value;
                });
            }

            if ($connection->get('log')) {
                $connectionConfig->setSQLLogger($app->get('prime-logger'));
            }

            if ($value = $cache->get('query')) {
                if (is_string($value)) {
                    $connectionConfig->setResultCache($app->make($value));
                } else {
                    $connectionConfig->setResultCache($app->make($value['class'], $value['options']));
                }
            }

            if ($value = $cache->get('metadata')) {
                if (is_string($value)) {
                    $connectionConfig->setMetadataCache($app->make($value));
                } else {
                    $connectionConfig->setMetadataCache($app->make($value['class'], $value['options']));
                }
            }
            
            return $connectionManager;
        });
        
        /**
         * Instantiator
         */
        $app->set('prime-instantiator', function() {
            return new RegistryInstantiator();
        });

        /**
         * MapperFactory
         */
        $app->set('prime-mapperFactory', function($app) {
            /** @var Application $app */

            return new MapperFactory($app->get('prime-mapperFactory-resolver'));
        });

        /**
         * NameResolver
         */
        $app->set('prime-mapperFactory-resolver', function() {
            return new SuffixResolver();
        });
        
        /**
         * logger
         */
        $app->set('prime-logger', function($app) {
            /** @var Application $app */

            if ($app->has('logger')) {
                return new PsrDecorator($app->get('logger'));
            }
            
            return new PsrDecorator();
        });
        
        /**
         * Migration
         */
        $app->set(VersionRepositoryInterface::class, function($app) {
            /** @var Application $app */

            $config = $app->subConfig('prime.migration');

            return new DbVersionRepository(
                $app->get('prime')->connection($config->get('connection')),
                $config->get('table', 'migrations')
            );
        });

        $app->set(MigrationProviderInterface::class, function($app) {
            return new FileMigrationProvider(
                $app->get(MigrationFactoryInterface::class),
                $app->config('prime.migration.path')
            );
        });

        $app->set(MigrationFactoryInterface::class, function($app) {
            return new MigrationFactory($app);
        });

        $app->set(MigrationManager::class, function($app) {
            return new MigrationManager(
                $app->get(VersionRepositoryInterface::class),
                $app->get(MigrationProviderInterface::class)
            );
        });

        $app->alias(MigrationManager::class, ['prime-migration-manager']);
        $app->alias(VersionRepositoryInterface::class, ['prime-migration-repository']);
        $app->alias(MigrationProviderInterface::class, ['prime-migration-provider']);

        /**
         * Serializer
         */
        $app->set('prime-serializer', function($app) {
            /** @var Application $app */

            if ($app->has(SerializerInterface::class)) {
                $serializer = $app->get(SerializerInterface::class);
            } else {
                $builder = SerializerBuilder::create();

                if ($cacheDir = $app->config('prime.serializer.cacheDir')) {
                    $builder->setCache(new Psr16Cache(new FilesystemAdapter('', 0, $cacheDir)));
                }

                $serializer = $builder->build();
            }

            $serializer->getLoader()
                ->addNormalizer(new PaginatorNormalizer())
                ->addNormalizer(new PrimeCollectionNormalizer($app->get('prime')))
            ;

            return $serializer;
        });

        /**
         * @see TypesRegistry
         */
        $app->set('prime-types-registry', function (Application $app) {
            $types = new TypesRegistry([
                'searchable_array'          => ArrayType::class, //for compatibility purpose
                TypeInterface::TARRAY       => ArrayType::class,
                TypeInterface::ARRAY_OBJECT => ArrayObjectType::class,
                TypeInterface::BOOLEAN      => BooleanType::class,
                TypeInterface::DATETIME     => DateTimeType::class,
                TypeInterface::DATETIMETZ   => DateTimeType::class,
                TypeInterface::DATE         => DateType::class,
                TypeInterface::JSON         => JsonType::class,
                TypeInterface::OBJECT       => ObjectType::class,
                TypeInterface::TIME         => TimeType::class,
                TypeInterface::TIMESTAMP    => TimestampType::class,
            ]);

            if ($app->has('prime-types')) {
                foreach ($app->get('prime-types') as $type => $classname) {
                    $types->register($classname, is_int($type) ? null : $type);
                }
            }

            return $types;
        });

        $app->alias('prime', [ServiceLocator::class]);
    }
    
    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        $config = $app->subConfig('prime');
        
        if ($config->get('facade')) {
            Prime::configure($app);
        }
        
        if ($config->get('activerecord') || $config->get('locatorizable')) {
            Locatorizable::configure(function() use($app) {
                return $app->get('prime');
            });
        }
    }

    /**
     * {@inheritdoc}
     */
    public function provideCommands(Console $console)
    {
        $console->lazy(function() use($console) {
            return new CacheCommand($console->getKernel()->get(ServiceLocator::class));
        }, CacheCommand::getDefaultName());

        $console->lazy(function() use($console) {
            return new CreateDatabaseCommand($console->getKernel()->get(ServiceLocator::class));
        }, CreateDatabaseCommand::getDefaultName());

        $console->lazy(function() use($console) {
            return new DropDatabaseCommand($console->getKernel()->get(ServiceLocator::class));
        }, DropDatabaseCommand::getDefaultName());

        $console->lazy(function() use($console) {
            return new EntityCommand($console->getKernel()->get(ServiceLocator::class));
        }, EntityCommand::getDefaultName());

        $console->lazy(function() use($console) {
            return new GraphCommand($console->getKernel()->get(ServiceLocator::class));
        }, GraphCommand::getDefaultName());

        $console->lazy(function() use($console) {
            return new HydratorGenerationCommand(
                $console->getKernel()->get(ServiceLocator::class),
                $console->getKernel()->config('prime.hydrators.loader')
            );
        }, HydratorGenerationCommand::getDefaultName());

        $console->lazy(function() use($console) {
            return new MapperCommand($console->getKernel()->get(ServiceLocator::class));
        }, MapperCommand::getDefaultName());

        $console->lazy(function() use($console) {
            return new UpgraderCommand($console->getKernel()->get(ServiceLocator::class));
        }, UpgraderCommand::getDefaultName());

        $console->lazy(function() use($console) {
            return new InitCommand($console->getKernel()->get(MigrationManager::class));
        }, InitCommand::getDefaultName());

        $console->lazy(function() use($console) {
            return new StatusCommand($console->getKernel()->get(MigrationManager::class));
        }, StatusCommand::getDefaultName());

        $console->lazy(function() use($console) {
            return new CheckCommand($console->getKernel()->get(MigrationManager::class));
        }, CheckCommand::getDefaultName());

        $console->lazy(function() use($console) {
            return new GenerateCommand($console->getKernel()->get(MigrationManager::class));
        }, GenerateCommand::getDefaultName());

        $console->lazy(function() use($console) {
            return new UpCommand($console->getKernel()->get(MigrationManager::class));
        }, UpCommand::getDefaultName());

        $console->lazy(function() use($console) {
            return new DownCommand($console->getKernel()->get(MigrationManager::class));
        }, DownCommand::getDefaultName());

        $console->lazy(function() use($console) {
            return new MigrateCommand($console->getKernel()->get(MigrationManager::class));
        }, MigrateCommand::getDefaultName());

        $console->lazy(function() use($console) {
            return new RollbackCommand($console->getKernel()->get(MigrationManager::class));
        }, RollbackCommand::getDefaultName());

        $console->lazy(function() use($console) {
            return new RedoCommand($console->getKernel()->get(MigrationManager::class));
        }, RedoCommand::getDefaultName());
    }
}
