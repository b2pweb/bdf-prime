<?php

namespace Bdf\Prime;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Entity\Hydrator\HydratorInterface;
use Bdf\Prime\Entity\Hydrator\HydratorRegistry;
use Bdf\Prime\Entity\Instantiator\InstantiatorInterface;
use Bdf\Prime\Entity\Instantiator\RegistryInstantiator;
use Bdf\Prime\Mapper\MapperFactory;
use Bdf\Prime\Mapper\MapperFactoryInterface;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Prime\Repository\RepositoryInterface;
use Bdf\Serializer\SerializerInterface;
use Psr\Container\ContainerInterface;

/**
 * ServiceLocator
 */
class ServiceLocator
{
    /**
     * @var ConnectionManager
     */
    private $connectionManager;

    /**
     * @var class-string-map<T, RepositoryInterface<T>>
     */
    private $repositories = [];

    /**
     * @var MapperFactoryInterface
     */
    private $mapperFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var \Closure
     */
    private $serializerResolver;

    /**
     * @var HydratorRegistry
     */
    private $hydrators;

    /**
     * @var InstantiatorInterface
     */
    private $instantiator;

    /**
     * DI container
     *
     * @var ContainerInterface
     */
    private $di;

    /**
     * SericeLocator constructor.
     *
     * @param ConnectionManager|null $connectionManager
     * @param MapperFactory|null $mapperFactory
     * @param InstantiatorInterface|null $instantiator
     */
    public function __construct(ConnectionManager $connectionManager = null, MapperFactoryInterface $mapperFactory = null, InstantiatorInterface $instantiator = null)
    {
        $this->connectionManager = $connectionManager ?: new ConnectionManager();
        $this->mapperFactory = $mapperFactory ?: new MapperFactory();
        $this->instantiator = $instantiator ?: new RegistryInstantiator();
        $this->hydrators = new HydratorRegistry();
    }

    /**
     * Returns connection manager
     *
     * @return ConnectionManager
     */
    public function connections()
    {
        return $this->connectionManager;
    }

    /**
     * Returns connection manager
     *
     * @return MapperFactoryInterface
     */
    public function mappers(): MapperFactoryInterface
    {
        return $this->mapperFactory;
    }

    /**
     * Get a db connection
     *
     * @param string $name
     *
     * @return ConnectionInterface
     */
    public function connection($name = null)
    {
        return $this->connectionManager->getConnection($name);
    }

    /**
     * Register a repository
     *
     * @param class-string<E> $entityClass
     * @param RepositoryInterface<E> $repository
     *
     * @template E as object
     *
     * @return void
     */
    public function registerRepository($entityClass, RepositoryInterface $repository): void
    {
        // https://github.com/vimeo/psalm/issues/4460
        /** @psalm-suppress InvalidPropertyAssignmentValue */
        $this->repositories[$entityClass] = $repository;
    }

    /**
     * Unregister a repository
     *
     * @param string $entityClass
     *
     * @return void
     */
    public function unregisterRepository($entityClass): void
    {
        if (isset($this->repositories[$entityClass]) && $this->repositories[$entityClass] instanceof EntityRepository) {
            $this->repositories[$entityClass]->destroy();
        }

        unset($this->repositories[$entityClass]);
    }

    /**
     * Get mapper for specified entity
     *
     * @param class-string<T>|T $entityClass Name of Entity object to load mapper for
     *
     * @return RepositoryInterface<T>|null
     * @template T as object
     *
     * @psalm-ignore-nullable-return
     */
    public function repository($entityClass): ?RepositoryInterface
    {
        if (is_object($entityClass)) {
            $entityClass = get_class($entityClass);
        }

        if (!isset($this->repositories[$entityClass])) {
            $mapper = $this->mapperFactory->build($this, $entityClass);

            if ($mapper === null) {
                return null;
            }

            $this->repositories[$entityClass] = $mapper->repository();
        }

        return $this->repositories[$entityClass];
    }

    /**
     * Get repository names
     *
     * @return array
     */
    public function repositoryNames()
    {
        return array_keys($this->repositories);
    }

    /**
     * Set the serializer
     *
     * @param \Closure|SerializerInterface $serializer
     *
     * @return $this
     */
    public function setSerializer($serializer)
    {
        if ($serializer instanceof \Closure) {
            $this->serializerResolver = $serializer;
        } elseif ($serializer instanceof SerializerInterface) {
            $this->serializer = $serializer;
        }

        return $this;
    }

    /**
     * Get the serializer
     *
     * @return SerializerInterface
     */
    public function serializer()
    {
        if ($this->serializerResolver !== null) {
            $resolver = $this->serializerResolver;
            $this->serializer = $resolver();
            $this->serializerResolver = null;
        }

        return $this->serializer;
    }

    /**
     * Get the entity hydrators registry
     *
     * @return HydratorRegistry
     */
    public function hydrators()
    {
        return $this->hydrators;
    }

    /**
     * Get the entity hydrator
     *
     * @param string|object $entity The entity class or object
     *
     * @return HydratorInterface
     */
    public function hydrator($entity)
    {
        if (is_object($entity)) {
            $entity = get_class($entity);
        }

        return $this->hydrators->get($entity);
    }

    /**
     * Get the entity instantiator
     *
     * @return InstantiatorInterface
     */
    public function instantiator()
    {
        return $this->instantiator;
    }

    /**
     * DI accessor
     *
     * @return ContainerInterface
     */
    public function di()
    {
        return $this->di;
    }

    /**
     * DI accessor
     *
     * @param ContainerInterface $di
     *
     * @return $this
     */
    public function setDI(ContainerInterface $di)
    {
        $this->di = $di;

        return $this;
    }

    /**
     * Clear all cache repositories
     *
     * @return void
     */
    public function clearRepositories(): void
    {
        foreach ($this->repositories as $repository) {
            if ($repository instanceof EntityRepository) {
                $repository->destroy();
            }
        }

        $this->repositories = [];
    }
}
