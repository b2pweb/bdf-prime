<?php

namespace Bdf\Prime\Connection\Result\FetchStrategy;

use Closure;

/**
 * Fetch all result as an instance of a class
 *
 * @template T as object
 * @implements ArrayFetchStrategyInterface<T>
 */
final class ClassArrayFetch implements ArrayFetchStrategyInterface
{
    /**
     * @var class-string<T>
     * @readonly
     */
    private string $className;

    /**
     * @var list<mixed>
     * @readonly
     */
    private array $constructorArguments;

    /**
     * @var Closure(array<string, mixed>, T):void
     * @readonly
     */
    private Closure $hydrator;

    /**
     * @param class-string<T> $className The result class name
     * @param list<mixed> $constructorArguments Constructor arguments to use
     */
    public function __construct(string $className, array $constructorArguments = [])
    {
        $this->className = $className;
        $this->constructorArguments = $constructorArguments;
        $this->hydrator = $this->createHydrator($className);
    }

    /**
     * {@inheritdoc}
     */
    public function one(array $row)
    {
        $object = new $this->className(...$this->constructorArguments);

        ($this->hydrator)($row, $object);

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function all(array $rows): array
    {
        $parsed = [];

        foreach ($rows as $row) {
            $parsed[] = $this->one($row);
        }

        return $parsed;
    }

    /**
     * Create the properties hydrator function
     *
     * @param class-string<T> $className
     * @return Closure(array<string, mixed>, T):void
     *
     * @psalm-ignore-falsable-return
     */
    private function createHydrator(string $className): Closure
    {
        /** @var Closure(array<string, mixed>, T):void */
        return Closure::bind(static function (array $row, object $entity) {
            foreach ($row as $property => $value) {
                $entity->$property = $value;
            }
        }, null, $className);
    }
}
