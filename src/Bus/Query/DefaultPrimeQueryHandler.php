<?php

namespace Bdf\Prime\Bus\Query;

use Bdf\Prime\Bus\Query\Configurator\ExecutionOptionInterface;
use Bdf\Prime\Bus\Query\Configurator\GlobalQueryConfiguratorInterface;
use Bdf\Prime\Bus\Query\Configurator\PropertyQueryConfiguratorInterface;
use Bdf\Prime\Bus\Query\ReturnType\ReturnTypeInterface;
use Bdf\Prime\Query\Contract\Whereable;
use Bdf\Prime\Query\Criteria\AttributeCriteriaLoader;
use Bdf\Prime\Query\ReadCommandInterface;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Prime\Repository\RepositoryInterface;
use Bdf\Prime\ServiceLocator;
use Exception;
use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionClass;

use function array_map;
use function is_string;
use function sprintf;

/**
 * Default handler that will be used by the {@see PrimeQueryBus} when no handler is found for the query class
 *
 * The query DTO must be annotated with {@see PrimeQuery} attribute to be handled by this handler.
 * Properties and attributes on those properties will be parsed to extract criteria to build the query filters,
 * and other query configuration.
 *
 * @implements QueryGeneratorPrimeQueryHandlerInterface<T>
 * @template T as object
 */
final class DefaultPrimeQueryHandler implements QueryGeneratorPrimeQueryHandlerInterface
{
    private readonly AttributeCriteriaLoader $criteriaLoader;
    private readonly PrimeQuery $target;

    /**
     * @var list<GlobalQueryConfiguratorInterface>
     */
    private readonly array $globalQueryConfigurator;

    /**
     * @var list<ExecutionOptionInterface>
     */
    private readonly array $globalExecutionOptions;

    /**
     * Map of properties names to their attributes
     *
     * @var array<string, list<PropertyQueryConfiguratorInterface|ExecutionOptionInterface>>
     */
    private readonly array $properties;

    private readonly ?string $relationOwnerProperty;
    private readonly ?string $relationName;

    public function __construct(
        /**
         * The handled query DTO class name
         *
         * @var class-string<T>
         */
        private readonly string $queryClass,
    ) {
        $this->criteriaLoader = new AttributeCriteriaLoader();

        $r = new ReflectionClass($this->queryClass);

        $this->target = $this->target($r);
        $this->globalQueryConfigurator = array_map(fn (ReflectionAttribute $attr) => $attr->newInstance(), $r->getAttributes(GlobalQueryConfiguratorInterface::class, ReflectionAttribute::IS_INSTANCEOF));
        $this->globalExecutionOptions = array_map(fn (ReflectionAttribute $attr) => $attr->newInstance(), $r->getAttributes(ExecutionOptionInterface::class, ReflectionAttribute::IS_INSTANCEOF));
        $this->properties = $this->loadProperties($r);

        // Add default values, if no "FromRelation" attribute is found
        $this->relationOwnerProperty ??= null;
        $this->relationName ??= null;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(ServiceLocator $prime, object $query, string|ReturnTypeInterface|null $returnType = null): mixed
    {
        $repository = $this->resolveRepository($prime, $query, $returnType);

        // A connection is explicitly defined with a repository
        // So, we need to change the connection of the repository before executing the query
        if ($repository !== null && $this->target->connection !== null) {
            /** @var EntityRepository<T> $repository */
            return $repository->on($this->target->connection, function ($repository) use ($query, $prime) {
                [$primeQuery, $options] = $this->generateQueryAndOptions($repository, $prime, $query);

                return $this->target->method->execute($primeQuery, $options);
            });
        }

        [$primeQuery, $options] = $this->generateQueryAndOptions($repository, $prime, $query);

        return $this->target->method->execute($primeQuery, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function generateQuery(ServiceLocator $prime, object $query, string|ReturnTypeInterface|null $returnType = null): ReadCommandInterface
    {
        $repository = $this->resolveRepository($prime, $query, $returnType);

        return $this->generateQueryAndOptions($repository, $prime, $query)[0];
    }

    /**
     * Try to generate the query and the execution options
     *
     * The query will not be executed, but is may use the connection to generate the query,
     * so in order to execute the query on a different connection, {@see EntityRepository::on()} should be used before calling this method.
     *
     * @param RepositoryInterface|null $repository The target repository
     * @param ServiceLocator $prime The prime service locator
     * @param T $query The query DTO
     *
     * @return list{ReadCommandInterface, array<string, mixed>} The configured query with the execution options
     */
    private function generateQueryAndOptions(?RepositoryInterface $repository, ServiceLocator $prime, object $query): array
    {
        $target = $this->target;

        if ($this->relationName !== null && $this->relationOwnerProperty !== null) {
            $owner = $query->{$this->relationOwnerProperty} ?? null;

            if (!$owner) {
                throw new InvalidArgumentException(sprintf('The query %s requires a valid entity on property %s', $this->queryClass, $this->relationOwnerProperty));
            }

            $relation = $prime->repository($owner)->relation($this->relationName);
            /** @var ReadCommandInterface $primeQuery */
            $primeQuery = $relation->link($owner);
            $options = $this->configureQuery($primeQuery, $query);

            return [$primeQuery, $options];
        }

        if ($repository === null && $target->connection === null) {
            throw new InvalidArgumentException(
                <<<EOF
                The repository or connection cannot be resolved for the execution of {$this->queryClass}. Please do one of the following:
                - Explicitly define the entity class name in the #[PrimeQuery(entity: xxx)] attribute
                - Define a connection name in the #[PrimeQuery(connection: xxx)] attribute
                - Provide a return type argument when calling the query bus e.g. \$bus->query(\$query, xxx::class) or \$bus->query(\$query, new xxxReturnType())
                - Declare a custom handler for this query class in the PrimeQueryBus
                EOF
            );
        }

        if ($repository !== null) {
            /** @var ReadCommandInterface $primeQuery */
            $primeQuery = $repository->queries()->builder();
            $options = $this->configureQuery($primeQuery, $query);

            return [$primeQuery, $options];
        }

        /** @var ReadCommandInterface $primeQuery */
        $primeQuery = $prime->connection($target->connection)->builder();
        $options = $this->configureQuery($primeQuery, $query);

        return [$primeQuery, $options];
    }

    /**
     * Try to resolve repository from the query DTO
     * - In case of relation query (i.e. FromRelation attribute is defined), the repository will be the relation repository
     * - If the entity is explicitly defined on PrimeQuery attribute, the repository will be resolved from this value
     * - If a return type is provided, and this type is an entity, the repository will be resolved from this type
     * - Otherwise, the repository will be null
     *
     * @param ServiceLocator $prime The prime service locator
     * @param T $query The query DTO
     * @param string|ReturnTypeInterface|null $returnType The return type
     *
     * @return RepositoryInterface|null
     */
    private function resolveRepository(ServiceLocator $prime, object $query, string|ReturnTypeInterface|null $returnType): ?RepositoryInterface
    {
        $target = $this->target;
        $entity = $target->entity;

        if ($this->relationName !== null && $this->relationOwnerProperty !== null) {
            $owner = $query->{$this->relationOwnerProperty} ?? null;

            if ($owner) {
                try {
                    return $prime->repository($owner)->relation($this->relationName)->relationRepository();
                } catch (Exception) {
                    // Ignore
                }
            }
        }

        if ($entity === null) {
            $entity = is_string($returnType) ? $returnType : $returnType?->unwrappedType();
        }

        if ($entity !== null) {
            return $prime->repository($entity);
        }

        return null;
    }

    /**
     * Apply filters and options to the query
     *
     * @param ReadCommandInterface $primeQuery The query to configure
     * @param object $queryDto The query DTO
     *
     * @return array<string, mixed> The execution options
     */
    private function configureQuery(ReadCommandInterface $primeQuery, object $queryDto): array
    {
        $options = [];

        foreach ($this->globalQueryConfigurator as $configurator) {
            $primeQuery = $configurator->configureQueryForDto($primeQuery, $queryDto);
        }

        foreach ($this->globalExecutionOptions as $attr) {
            $options = [...$options, ...$attr->executionOptions()];
        }

        /** @var Whereable&ReadCommandInterface $primeQuery */
        $primeQuery->where($this->criteriaLoader->criteria($queryDto));

        foreach ($this->properties as $property => $attributes) {
            $value = $queryDto->$property ?? null;

            foreach ($attributes as $attr) {
                if ($attr instanceof PropertyQueryConfiguratorInterface) {
                    $primeQuery = $attr->configureQueryForProperty($primeQuery, $value);
                }

                if ($attr instanceof ExecutionOptionInterface) {
                    $options = [...$options, ...$attr->executionOptions($value)];
                }
            }
        }

        return $options;
    }

    /**
     * @param ReflectionClass<T> $r
     */
    private function target(ReflectionClass $r): PrimeQuery
    {
        foreach ($r->getAttributes(PrimeQuery::class) as $attr) {
            return $attr->newInstance();
        }

        throw new InvalidArgumentException(sprintf('The query %s must be annotated with #[PrimeQuery]', $this->queryClass));
    }

    /**
     * Load properties attributes
     *
     * @param ReflectionClass<T> $r
     * @return array<string, list<PropertyQueryConfiguratorInterface|ExecutionOptionInterface>>
     */
    private function loadProperties(ReflectionClass $r): array
    {
        $properties = [];

        foreach ($r->getProperties() as $property) {
            $attributes = [];

            foreach ($property->getAttributes(PropertyQueryConfiguratorInterface::class, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                $attributes[] = $attr->newInstance();
            }

            foreach ($property->getAttributes(ExecutionOptionInterface::class, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                // Attributes with both interfaces are ExecutionOptionInterface and PropertyQueryConfiguratorInterface are already loaded
                // So do not add here to avoid duplicate
                if (!is_subclass_of($attr->getName(), PropertyQueryConfiguratorInterface::class)) {
                    $attributes[] = $attr->newInstance();
                }
            }

            foreach ($property->getAttributes(FromRelation::class) as $attr) {
                /** @psalm-suppress InaccessibleProperty */
                $this->relationOwnerProperty = $property->getName();
                /** @psalm-suppress InaccessibleProperty */
                $this->relationName = $attr->newInstance()->relation;
                break;
            }

            $properties[$property->getName()] = $attributes;
        }

        return $properties;
    }
}
