<?php

namespace Bdf\Prime\Bus\Command;

use Bdf\Prime\Bus\Command\WriteMethod\WriteMethodInterface;
use Bdf\Prime\Bus\Command\WriteQuery\WriteQueryInterface;
use Bdf\Prime\Query\Criteria\AttributeCriteriaLoader;
use Bdf\Prime\ServiceLocator;
use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionClass;

/**
 * Default prime command handler, using attributes to define the write methods and queries
 *
 * Will be used by the {@see PrimeCommandBus} to execute commands with no associated handler.
 * It will parse:
 * - All properties annotated with {@see WriteMethodInterface} to perform write operations on entities
 * - Class attributes implementing {@see WriteQueryInterface} to perform low level write operations
 * - Properties annotated with {@see SetValue} to extract values for the update query
 *
 * @template T as object
 */
final class DefaultPrimeCommandHandler implements CommandMetadataExtractorInterface
{
    private static ?AttributeCriteriaLoader $criteriaLoader = null;

    /**
     * Writes methods to apply
     * The key is the property name, and the value is the write method to execute
     *
     * @var array<string, WriteMethodInterface>
     */
    private readonly array $writes;

    /**
     * Write queries to build and execute
     * Will be extracted from class level attributes
     *
     * @var list<WriteQueryInterface>
     */
    private readonly array $queries;

    /**
     * Properties that are used as values for the update query
     * Should be annotated with {@see SetValue} attribute
     *
     * @var array<string, SetValue>
     */
    private readonly array $valueProperties;

    public function __construct(
        /**
         * The command class name
         *
         * @var class-string<T>
         */
        private readonly string $className,
    ) {
        $writes = [];
        $values = [];
        $queries = [];

        $r = new ReflectionClass($this->className);

        foreach ($r->getAttributes(WriteQueryInterface::class, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
            $queries[] = $attr->newInstance();
        }

        foreach ($r->getProperties() as $property) {
            foreach ($property->getAttributes(WriteMethodInterface::class, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                $writes[$property->getName()] = $attr->newInstance();
            }

            // Load "SetValue" attributes only if it can be used by the command
            if ($queries) {
                foreach ($property->getAttributes(SetValue::class) as $attr) {
                    $values[$property->getName()] = $attr->newInstance();
                }
            }
        }

        $this->writes = $writes;
        $this->queries = $queries;
        $this->valueProperties = $values;
    }

    /**
     * @param ServiceLocator $prime The prime service locator
     * @param T $command Command to execute
     * @param TransactionManager $transactions Utility class for starting transactions
     */
    public function __invoke(ServiceLocator $prime, object $command, TransactionManager $transactions): void
    {
        $repositories = [];
        $writes = [];

        foreach ($this->writes as $property => $write) {
            $entity = $command->$property ?? null;

            if (!$entity) {
                continue;
            }

            $entityClass = $write->entityClassName() ?? $entity::class;

            if (!$repositories[$entityClass] ??= $prime->repository($entityClass)) {
                throw new InvalidArgumentException(sprintf('The entity %s is not managed by Prime', $entityClass));
            }

            $writes[] = static fn () => $write->execute($repositories[$entityClass], $entity);
        }

        foreach ($this->queries as $query) {
            $entityClass = $query->entity();

            if (!$repositories[$entityClass] ??= $prime->repository($entityClass)) {
                throw new InvalidArgumentException(sprintf('The entity %s is not managed by Prime', $entityClass));
            }

            $writes[] = fn () => $query->execute($repositories[$entityClass], $command, $this);
        }

        foreach ($repositories as $repository) {
            $transactions->start($repository->connection()); // @todo check return ?
        }

        foreach ($writes as $write) {
            $write();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function criteria(object $command): iterable
    {
        $loader = (self::$criteriaLoader ??= new AttributeCriteriaLoader());

        return $loader->criteria($command);
    }

    /**
     * {@inheritdoc}
     */
    public function values(object $command): array
    {
        $values = [];

        foreach ($this->valueProperties as $property => $setValue) {
            $value = $command->$property ?? null;

            if ($value !== null || !$setValue->skipNull) {
                $values[$setValue->field ?? $property] = $setValue->value($value);
            }
        }

        return $values;
    }
}
