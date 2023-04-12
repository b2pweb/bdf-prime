<?php

namespace Bdf\Prime\Query\Closure\Parser;

use Bdf\Prime\Query\Closure\Filter\AtomicFilter;
use Bdf\Prime\Query\Closure\Value\LikeValue;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\VariadicPlaceholder;
use RuntimeException;

/**
 * Parse predicate function calls
 *
 * Handle the following functions:
 * - str_contains : perform a LIKE %value% query
 * - str_starts_with : perform a LIKE value% query
 * - str_ends_with : perform a LIKE %value query
 * - in_array : perform an IN query
 */
final class FunctionCallParser
{
    private EntityAccessorParser $entityAccessorParser;
    private ValueParser $valueParser;

    /**
     * Map function name to parser
     *
     * @var array<string, callable(Arg[]):AtomicFilter>
     */
    private array $functions;

    public function __construct(EntityAccessorParser $entityAccessorParser, ValueParser $valueParser)
    {
        $this->entityAccessorParser = $entityAccessorParser;
        $this->valueParser = $valueParser;

        $this->functions = [
            'str_contains' => [$this, 'parseStrContains'],
            'str_starts_with' => [$this, 'parseStartsWith'],
            'str_ends_with' => [$this, 'parseEndsWith'],
            'in_array' => [$this, 'parseInArray'],
        ];
    }

    /**
     * Parse a predicate function call
     *
     * @param FuncCall $expr The function call node
     *
     * @return AtomicFilter
     */
    public function parse(FuncCall $expr): AtomicFilter
    {
        $function = $expr->name->toString();

        if (!isset($this->functions[$function])) {
            throw new RuntimeException('Unsupported function call ' . $function . ' in filters. Supported functions are: ' . implode(', ', array_keys($this->functions)) . '.');
        }

        $args = $expr->args;

        foreach ($args as $arg) {
            if ($arg instanceof VariadicPlaceholder || $arg->unpack) {
                throw new RuntimeException('Unsupported unpacking in function call ' . $function . ' in filters.');
            }
        }

        /** @var Arg[] $args */
        return $this->functions[$function]($args);
    }

    private function parseStrContains(array $args): AtomicFilter
    {
        return new AtomicFilter(
            $this->entityAccessorParser->parse($args[0]->value),
            ':like',
            LikeValue::contains($this->valueParser->parse($args[1]->value)),
        );
    }

    private function parseStartsWith(array $args): AtomicFilter
    {
        return new AtomicFilter(
            $this->entityAccessorParser->parse($args[0]->value),
            ':like',
            LikeValue::startsWith($this->valueParser->parse($args[1]->value)),
        );
    }

    private function parseEndsWith(array $args): AtomicFilter
    {
        return new AtomicFilter(
            $this->entityAccessorParser->parse($args[0]->value),
            ':like',
            LikeValue::endsWith($this->valueParser->parse($args[1]->value)),
        );
    }

    private function parseInArray(array $args): AtomicFilter
    {
        return new AtomicFilter(
            $this->entityAccessorParser->parse($args[0]->value),
            ':in',
            $this->valueParser->parse($args[1]->value),
        );
    }
}
