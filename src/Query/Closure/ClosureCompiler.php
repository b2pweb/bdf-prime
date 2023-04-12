<?php

namespace Bdf\Prime\Query\Closure;

use Bdf\Prime\Query\Closure\Filter\AndFilter;
use Bdf\Prime\Query\Closure\Filter\OrFilter;
use Bdf\Prime\Query\Contract\Whereable;
use Bdf\Prime\Repository\RepositoryInterface;
use Closure;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use InvalidArgumentException;
use LogicException;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Psr\SimpleCache\CacheInterface;
use ReflectionFunction;
use ReflectionNamedType;

use ReflectionType;

use ReflectionUnionType;

use function count;
use function file_get_contents;
use function md5;
use function sprintf;
use function str_contains;
use function strchr;

/**
 * Parse a closure filter and compile it to a where filter builder function
 *
 * @template E as object
 */
final class ClosureCompiler
{
    private static ?Parser $parser = null;

    /**
     * @var RepositoryInterface<E>
     */
    private RepositoryInterface $repository;
    private ?CacheInterface $cache;

    /**
     * @param RepositoryInterface<E> $repository
     * @param CacheInterface|null $cache
     */
    public function __construct(RepositoryInterface $repository, ?CacheInterface $cache = null)
    {
        $this->repository = $repository;
        $this->cache = $cache;
    }

    /**
     * Compile a predicate closure to a where filter builder function
     *
     * Usage:
     * <code>
     * $query->where($compiler->compile(fn (MyEntity $e) => $e->id != 5 && $e->name == 'foo'));
     * </code>
     *
     * @param Closure(E):bool $closure The predicate closure
     *
     * @return callable(Whereable):void
     *
     * @see Whereable::where() Should be used with the returned value of this method
     */
    public function compile(Closure $closure): callable
    {
        $reflection = new ReflectionFunction($closure);

        return $this->normalizeFilters($reflection, $this->load($reflection));
    }

    /**
     * Try to load the filters from cache or parse the closure
     *
     * @param ReflectionFunction $reflection
     * @return AndFilter
     */
    private function load(ReflectionFunction $reflection): AndFilter
    {
        if ($this->cache) {
            $key = 'prime.closure.' . md5($reflection->getFileName() . $reflection->getStartLine());

            if ($filters = $this->cache->get($key)) {
                return $filters;
            }
        }

        $filters = $this->parseClosure($reflection);

        if ($this->cache) {
            $this->cache->set($key, $filters);
        }

        return $filters;
    }

    private function parseClosure(ReflectionFunction $reflection): AndFilter
    {
        if (!class_exists(ParserFactory::class)) {
            throw new LogicException('Closure filters requires the "nikic/php-parser" package. Please install it with "composer require nikic/php-parser"');
        }

        if ($reflection->getNumberOfParameters() !== 1) {
            throw new InvalidArgumentException('Closure must have only one parameter');
        }

        $parameter = $reflection->getParameters()[0];
        $this->checkParameterType($parameter->getType());

        if (self::$parser === null) {
            self::$parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        }

        $ast = self::$parser->parse(file_get_contents($reflection->getFileName()));
        $traverser = new NodeTraverser();
        $traverser->addVisitor($extractor = new ClosureFiltersExtractorVisitor($reflection));
        $traverser->traverse($ast);

        $filters = $extractor->filters();

        $this->validateFilters($filters);

        return $filters;
    }

    /**
     * @psalm-suppress UndefinedClass - ReflectionUnionType does not exist on PHP < 8.0
     * @psalm-suppress TypeDoesNotContainType
     */
    private function checkParameterType(?ReflectionType $type): void
    {
        if ($type === null) {
            throw new InvalidArgumentException('Closure parameter must declare the entity type');
        }

        $types = $type instanceof ReflectionUnionType ? $type->getTypes() : [$type];
        $typeName = null;

        foreach ($types as $atomicType) {
            if (!$atomicType instanceof ReflectionNamedType) {
                continue;
            }

            $typeName = $atomicType->getName();

            if ($typeName === $this->repository->entityName()) {
                return;
            }

            if (is_subclass_of($this->repository->entityName(), $typeName)) {
                return;
            }
        }

        throw new InvalidArgumentException(sprintf('Expect parameter of type "%s" but get "%s"', $this->repository->entityName(), $typeName));
    }

    private function validateFilters(AndFilter $filters): void
    {
        $metadata = $this->repository->metadata();

        foreach ($filters->filters as $filter) {
            if ($filter instanceof OrFilter) {
                foreach ($filter->filters as $subFilters) {
                    $this->validateFilters($subFilters);
                }
                continue;
            }

            $property = $filter->property;

            if ($metadata->attributeExists($property)) {
                continue;
            }

            // Check if it's on an embedded or relation
            if (str_contains($property, '.') && $metadata->embedded(strchr($property, '.', true))) {
                continue;
            }

            throw new InvalidArgumentException(sprintf('Property "%s" is not mapped to database.', $filter->property));
        }
    }

    private function normalizeFilters(ReflectionFunction $reflection, AndFilter $compiledFilters): NestedFilters
    {
        $filters = [];

        foreach ($compiledFilters as $filter) {
            if ($filter instanceof OrFilter) {
                $filters[] = [$this->normalizeOrFilters($reflection, $filter), null, null];
                continue;
            }

            $filters[] = [
                $filter->property,
                $filter->operator,
                $filter->value->get($reflection)
            ];
        }

        return new NestedFilters($filters);
    }

    private function normalizeOrFilters(ReflectionFunction $reflection, OrFilter $compiledFilters): NestedFilters
    {
        $filters = [];

        foreach ($compiledFilters->filters as $subFilters) {
            if (count($subFilters) !== 1) {
                $filters[] = [$this->normalizeFilters($reflection, $subFilters), null, null];
                continue;
            }

            $filters[] = [
                $subFilters[0]->property,
                $subFilters[0]->operator,
                $subFilters[0]->value->get($reflection)
            ];
        }

        return new NestedFilters($filters, CompositeExpression::TYPE_OR);
    }
}
