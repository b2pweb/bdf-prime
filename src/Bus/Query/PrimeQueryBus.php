<?php

namespace Bdf\Prime\Bus\Query;

use Bdf\Prime\Bus\Query\ReturnType\ReturnTypeInterface;
use Bdf\Prime\Query\ReadCommandInterface;
use Bdf\Prime\ServiceLocator;
use InvalidArgumentException;
use TypeError;

use function is_string;

/**
 * Default implementation of the PrimeQueryBusInterface
 * Will check for the handler associated to the query class, and if not found, will use the {@see DefaultPrimeQueryHandler}.
 *
 * Handler are callables that takes as parameters:
 * - Prime service locator {@see ServiceLocator}
 * - The query object
 * - The return type, which can be the result class name, an instance of {@see ReturnTypeInterface}, or null
 * And will return the result of the query execution.
 */
final class PrimeQueryBus implements PrimeQueryBusInterface
{
    public function __construct(
        private readonly ServiceLocator $prime,

        /**
         * Map of handlers, with the key as the query DTO class name, and the value as the handler function.
         *
         * @var array<class-string, callable(ServiceLocator, object, class-string|ReturnTypeInterface|null):mixed>
         * @psalm-var class-string-map<T, callable(ServiceLocator, T, class-string|ReturnTypeInterface|null):mixed>
         */
        private array $handlers = [],
    ) {}

    /**
     * {@inheritdoc}
     */
    public function query(object $query, string|ReturnTypeInterface|null $returnType = null): mixed
    {
        $queryClass = $query::class;
        $handler = $this->handlers[$queryClass] ??= new DefaultPrimeQueryHandler($queryClass);

        $value = $handler($this->prime, $query, $returnType);

        if ($returnType === null) {
            return $value;
        }

        if (is_string($returnType)) {
            if ($value !== null && !$value instanceof $returnType) {
                throw new TypeError('The query result must be an instance of ' . $returnType);
            }

            return $value;
        }

        return $returnType->cast($value);
    }

    /**
     * Try to generate the prime query which will be executed by the {@see PrimeQueryBus::query()} is same parameters are provided.
     * This method should only be used for debugging or testing purposes.
     *
     * The related handle must implements {@see QueryGeneratorPrimeQueryHandlerInterface} and execute only a single query.
     *
     * Note: Because the query is only generated and not executed, some options like pagination, limit, etc. may not be applied.
     *       Only the base query, with filters and some global options, will be configured.
     *
     * @param object $query The query DTO
     * @param string|ReturnTypeInterface|null $returnType The expected return type of the query
     *
     * @return ReadCommandInterface The generated query
     */
    public function generateQuery(object $query, string|ReturnTypeInterface|null $returnType = null): ReadCommandInterface
    {
        $queryClass = $query::class;
        $handler = $this->handlers[$queryClass] ??= new DefaultPrimeQueryHandler($queryClass);

        if (!$handler instanceof QueryGeneratorPrimeQueryHandlerInterface) {
            throw new InvalidArgumentException(sprintf(
                'The handler for query %s must implement %s',
                $queryClass,
                QueryGeneratorPrimeQueryHandlerInterface::class
            ));
        }

        return $handler->generateQuery($this->prime, $query, $returnType);
    }
}
