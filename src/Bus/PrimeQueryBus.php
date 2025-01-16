<?php

namespace Bdf\Prime\Bus;

use Bdf\Prime\ServiceLocator;
use TypeError;

use function get_class;
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
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function query(object $query, $returnType = null): mixed
    {
        $queryClass = get_class($query);
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
}
