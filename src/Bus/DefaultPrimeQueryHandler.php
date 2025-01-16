<?php

namespace Bdf\Prime\Bus;

use Bdf\Prime\Query\Criteria\Criterion;
use Bdf\Prime\Query\Criteria\FilterEntry;
use Bdf\Prime\Query\ReadCommandInterface;
use Bdf\Prime\ServiceLocator;
use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionClass;

use function is_string;
use function sprintf;

/**
 * Default handler that will be used by the {@see PrimeQueryBus} when no handler is found for the query class
 *
 * The query DTO must be annotated with {@see PrimeQuery} attribute to be handled by this handler.
 * Properties and attributes on those properties will be parsed to extract criteria to build the query filters,
 * and other query configuration.
 *
 * @template T as object
 */
final class DefaultPrimeQueryHandler
{
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
        // @todo charger le metadata autrement
        $r = new ReflectionClass($this->queryClass);

        foreach ($r->getProperties() as $property) {
            foreach ($property->getAttributes(FromRelation::class) as $attr) {
                $this->relationOwnerProperty = $property->getName();
                $this->relationName = $attr->newInstance()->relation;
                break;
            }
        }

        $this->relationOwnerProperty ??= null;
        $this->relationName ??= null;
    }

    /**
     * @param ServiceLocator $prime
     * @param T $query
     * @param string|ReturnTypeInterface|null $returnType
     *
     * @return mixed
     */
    public function __invoke(ServiceLocator $prime, object $query, string|ReturnTypeInterface|null $returnType = null): mixed
    {
        $target = $this->target($query);
        $entity = $target->entity;
        $repository = null;

        if ($this->relationName !== null && $this->relationOwnerProperty !== null) {
            $owner = $query->{$this->relationOwnerProperty} ?? null;

            if (!$owner) {
                throw new InvalidArgumentException(sprintf('The query %s requires a valid entity on property %s', $this->queryClass, $this->relationOwnerProperty));
            }

            $relation = $prime->repository($owner)->relation($this->relationName);
            return $this->executeQuery($relation->link($owner), $query, $target);
        }

        if ($entity === null) {
            $entity = is_string($returnType) ? $returnType : $returnType?->unwrappedType();
        }

        if ($entity !== null) {
            $repository = $prime->repository($entity);
        }

        if ($repository === null && $target->connection === null) {
            throw new InvalidArgumentException(<<<EOF
                The repository or connection cannot be resolved for the execution of {$this->queryClass}. Please do one of the following:
                - Explicitly define the entity class name in the #[PrimeQuery(entity: xxx)] attribute
                - Define a connection name in the #[PrimeQuery(connection: xxx)] attribute
                - Provide a return type argument when calling the query bus e.g. \$bus->query(\$query, xxx::class) or \$bus->query(\$query, new xxxReturnType())
                - Declare a custom handler for this query class in the PrimeQueryBus
                EOF
            );
        }

        if ($repository !== null) {
            if ($target->connection === null) {
                return $this->executeQuery($repository->queries()->builder(), $query, $target);
            }

            return $repository->on($target->connection, fn() => $this->executeQuery($repository->queries()->builder(), $query, $target));
        }

        return $this->executeQuery($prime->connection($target->connection)->builder(), $query, $target);
    }

    private function executeQuery(ReadCommandInterface $primeQuery, object $queryDto, PrimeQuery $target): mixed
    {
        // @todo save metadata on the current class
        $r = new ReflectionClass($queryDto);
        $options = [];

        foreach ($r->getAttributes(GlobalQueryConfiguratorInterface::class, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
            $primeQuery = $attr->newInstance()->configureQueryForDto($primeQuery, $queryDto);
        }

        foreach ($r->getAttributes(ExecutionOptionInterface::class, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
            $options = [...$options, ...$attr->newInstance()->executionOptions()];
        }

        $primeQuery->where($this->filters($queryDto));

        foreach ($r->getProperties() as $property) {
            $value = $property->getValue($queryDto);

            foreach ($property->getAttributes(PropertyQueryConfiguratorInterface::class, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                $primeQuery = $attr->newInstance()->configureQueryForProperty($primeQuery, $value);
            }
            foreach ($property->getAttributes(ExecutionOptionInterface::class, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                $options = [...$options, ...$attr->newInstance()->executionOptions($value)];
            }
        }

        return $target->method->execute($primeQuery, $options);
    }

    private function target(object $query): PrimeQuery
    {
        $r = new ReflectionClass($query);

        foreach ($r->getAttributes(PrimeQuery::class) as $attr) {
            return $attr->newInstance();
        }

        throw new InvalidArgumentException(sprintf('The query %s must be annotated with #[PrimeQuery]', $this->queryClass));
    }

    private function filters(object $query): iterable
    {
        // @todo factoriser avec le système de critieria
        foreach ($this->loadCriteria($query) as $property => $criterion) {
            $value = $query->$property ?? null;

            if ($value === null && $criterion->skipNull) {
                continue;
            }

            $field = $criterion->field($property);
            $value = $criterion->value($value);

            if (is_string($field)) {
                if ($criterion->operator !== null) {
                    $field .= ' ' . $criterion->operator;
                }

                yield $field => $value;
                continue;
            }

            yield $property => new FilterEntry($field, $criterion->operator ?? '=', $value);
        }
    }

    /**
     * Load criterion per property
     * This method can be overridden. By default, it will load using the {@see Criterion} attribute on properties
     *
     * @return array<string, Criterion>
     */
    private function loadCriteria(object $query): array
    {
        $criteria = [];

        foreach ((new ReflectionClass($query))->getProperties() as $property) {
            foreach ($property->getAttributes(Criterion::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                $criteria[$property->getName()] = $attribute->newInstance();
                break; // Keep only the first attribute
            }
        }

        return $criteria;
    }
}
