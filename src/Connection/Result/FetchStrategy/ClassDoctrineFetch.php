<?php

namespace Bdf\Prime\Connection\Result\FetchStrategy;

use Doctrine\DBAL\Result;

/**
 * Fetch all result as an instance of a class
 *
 * @template T as object
 * @implements DoctrineFetchStrategyInterface<T>
 */
final class ClassDoctrineFetch implements DoctrineFetchStrategyInterface
{
    /**
     * @var ClassArrayFetch<T>
     * @readonly
     */
    private $arrayFetch;

    /**
     * @param class-string<T> $className The result class name
     * @param list<mixed> $constructorArguments Constructor arguments to use
     */
    public function __construct(string $className, array $constructorArguments = [])
    {
        $this->arrayFetch = new ClassArrayFetch($className, $constructorArguments);
    }

    /**
     * {@inheritdoc}
     */
    public function one(Result $result)
    {
        $value = $result->fetchAssociative();

        return $value ? $this->arrayFetch->one($value) : false;
    }

    /**
     * {@inheritdoc}
     */
    public function all(Result $result): array
    {
        return $this->arrayFetch->all($result->fetchAllAssociative());
    }
}
