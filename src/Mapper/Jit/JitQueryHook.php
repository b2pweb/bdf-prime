<?php

namespace Bdf\Prime\Mapper\Jit;

use Bdf\Prime\Query\CommandInterface;
use Bdf\Prime\Query\Contract\Compilable;
use Bdf\Prime\Query\Contract\JitCompilable;
use Bdf\Prime\Query\QueryRepositoryExtension;

/**
 * @method mixed __invoke(...$args)
 */
abstract class JitQueryHook
{
    protected object $mapper;
    private bool $ignoreErrors;

    /**
     * The compiled query
     *
     * @var string|null
     */
    public ?string $query = null;

    /**
     * Map the binding index to the argument index
     *
     * @var array|null
     */
    public ?array $argumentsMapping = null;

    /**
     * Map the binding index to the constant value
     *
     * @var array|null
     */
    public ?array $constants = null;

    /**
     * Map the metadata index to the argument index
     *
     * @var array|null
     * @see JitCompilable::getMetadata()
     */
    public ?array $metadataMapping = null;

    /**
     * Map the metadata index to a constant value
     *
     * @var array|null
     * @see JitCompilable::getMetadata()
     */
    public ?array $metadataConstants = null;

    /**
     * The query extension parameters
     */
    public ?array $extensionParameters = null;

    /**
     * The number of times the query has been compiled
     */
    public int $count = 0;

    /**
     * Whether the query is invalid
     * If true, the query cannot be compiled anymore
     */
    public bool $invalid = false;

    /**
     * Whether the query has ambiguous bindings (multiple arguments for the same binding)
     */
    public bool $ambiguous = false;

    /**
     * The reason of the invalidation
     */
    public ?string $reason = null;

    public function __construct(object $mapper, bool $ignoreErrors = true)
    {
        $this->mapper = $mapper;
        $this->ignoreErrors = $ignoreErrors;
    }

    /**
     * The mapper defining the hooked method
     */
    abstract public function class(): string;

    /**
     * The hooked method identifier
     * It can be different from the method name, if the method is aliased
     */
    abstract public function method(): string;

    /**
     * @param Q $query
     * @param array $arguments
     *
     * @return Q
     *
     * @template Q as CommandInterface
     */
    final public function hook(CommandInterface $query, array $arguments): CommandInterface
    {
        if ($this->invalid) {
            return $query;
        }

        if (!$query instanceof JitCompilable || $query->type() !== Compilable::TYPE_SELECT) {
            $this->invalidate('Cannot get the SQL of the query');

            return $query;
        }

        $extension = $query->getExtension();

        if (!$extension instanceof QueryRepositoryExtension) {
            $this->invalidate('The JIT system supports only queries with the QueryRepositoryExtension extension');

            return $query;
        }

        ++$this->count;

        $sql = $query->toSql();

        if ($this->query && $sql !== $this->query) {
            $this->invalidate('The compiled query has changed');

            return $query;
        }

        $this->query = $sql;

        [$argumentsMapping, $constants, $this->ambiguous] = $this->extractArgumentsMapping($arguments, $query->getBindings());

        if ($this->ambiguous) {
            return $query;
        }

        if ($this->argumentsMapping && $argumentsMapping !== $this->argumentsMapping) {
            $this->invalidate('The arguments mapping has changed');

            return $query;
        }

        if ($this->constants && $constants !== $this->constants) {
            $this->invalidate('The constants has changed');

            return $query;
        }

        $this->argumentsMapping = $argumentsMapping;
        $this->constants = $constants;

        [$metadataMapping, $metadataConstants, $this->ambiguous] = $this->extractArgumentsMapping($arguments, $query->getMetadata());

        if ($this->ambiguous) {
            return $query;
        }

        if ($this->metadataMapping && $metadataMapping !== $this->metadataMapping) {
            $this->invalidate('The metadata mapping has changed');

            return $query;
        }

        if ($this->metadataConstants && $metadataConstants !== $this->metadataConstants) {
            $this->invalidate('The metadata constants has changed');

            return $query;
        }

        $this->metadataMapping = $metadataMapping;
        $this->metadataConstants = $metadataConstants;

        $extensionParameters = $extension->getMetadata();

        if ($this->extensionParameters && $extensionParameters !== $this->extensionParameters) {
            $this->invalidate('The extension parameters has changed');

            return $query;
        }

        $this->extensionParameters = $extensionParameters;

        return $query;
    }

    public function invalidate(string $reason): void
    {
        $this->invalid = true;
        $this->reason = $reason;

        if (!$this->ignoreErrors) {
            throw new JitException($this->class(), $this->method(), $reason);
        }
    }

    /**
     * Try to find the mapping between the arguments and the bindings
     *
     * @param array $arguments The arguments of the method
     * @param array $toMap The binding, or metadata, which should be mapped with the arguments
     *
     * @return array{0: array, 1: array, 2: bool} First is the mapping, second is the constants, third is whether the mapping is ambiguous
     */
    private function extractArgumentsMapping(array $arguments, array $toMap): array
    {
        $mapping = [];
        $constants = [];
        $ambiguous = false;

        foreach ($toMap as $key => $value) {
            $matchingArgIndex = null;
            $matchingCount = 0;

            // Check the match between the binding and the arguments
            // If multiple arguments match the same binding, we mark it as ambiguous
            foreach ($arguments as $argIndex => $argValue) {
                if ($argValue === $value) {
                    ++$matchingCount;
                    $matchingArgIndex = $argIndex;
                }
            }

            if ($matchingCount > 1) {
                $ambiguous = true;
                break;
            }

            if ($matchingArgIndex === null) {
                $constants[$key] = $value;
                continue;
            }

            $mapping[$key] = $matchingArgIndex;
        }

        return [$mapping, $constants, $ambiguous];
    }
}
