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
use Closure;
use Psr\Container\ContainerInterface;

/**
 * ServiceLocator
 */
final class ServiceLocator
{
    private ConnectionManager $connectionManager;

    /**
     * @var class-string-map<T, RepositoryInterface<T>>
     */
    private array $repositories = [];
    private MapperFactoryInterface $mapperFactory;
    private SerializerInterface $serializer;
    private ?Closure $serializerResolver = null;
    private HydratorRegistry $hydrators;
    private InstantiatorInterface $instantiator;
    private ?ContainerInterface $di = null;

    /**
     * SericeLocator constructor.
     *
     * @param ConnectionManager|null $connectionManager
     * @param MapperFactory|null $mapperFactory
     * @param InstantiatorInterface|null $instantiator
     */
    public function __construct(?ConnectionManager $connectionManager = null, ?MapperFactoryInterface $mapperFactory = null, ?InstantiatorInterface $instantiator = null)
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
    public function connections(): ConnectionManager
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
    public function connection(?string $name = null): ConnectionInterface
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
    public function registerRepository(string $entityClass, RepositoryInterface $repository): void
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
    public function unregisterRepository(string $entityClass): void
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
    public function repository(string|object $entityClass): ?RepositoryInterface
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
    public function repositoryNames(): array
    {
        return array_keys($this->repositories);
    }

    /**
     * Set the serializer
     *
     * @param Closure|SerializerInterface $serializer
     *
     * @return $this
     */
    public function setSerializer(Closure|SerializerInterface $serializer): static
    {
        if ($serializer instanceof Closure) {
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
    public function serializer(): SerializerInterface
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
    public function hydrators(): HydratorRegistry
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
    public function hydrator(string|object $entity): HydratorInterface
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
    public function instantiator(): InstantiatorInterface
    {
        return $this->instantiator;
    }

    /**
     * DI accessor
     *
     * @return ContainerInterface|null
     */
    public function di(): ?ContainerInterface
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
    public function setDI(ContainerInterface $di): static
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
