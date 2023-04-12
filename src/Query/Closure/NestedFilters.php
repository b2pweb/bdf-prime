<?php

namespace Bdf\Prime\Query\Closure;

use Bdf\Prime\Query\Contract\Whereable;
use Doctrine\DBAL\Query\Expression\CompositeExpression;

/**
 * Adapt array of filters to a closure compatible with Whereable::where()
 *
 * @see Whereable::where()
 * @see Whereable::nested()
 *
 * @internal
 */
final class NestedFilters
{
    /**
     * @var list<array{string|NestedFilters, string|null, mixed|null}>
     */
    private array $filters;

    /**
     * @var CompositeExpression::TYPE_AND|CompositeExpression::TYPE_OR
     */
    private string $type;

    /**
     * @param list<array{string|NestedFilters, string|null, mixed|null}> $filters
     * @param CompositeExpression::TYPE_AND|CompositeExpression::TYPE_OR $type
     */
    public function __construct(array $filters, string $type = CompositeExpression::TYPE_AND)
    {
        $this->filters = $filters;
        $this->type = $type;
    }

    public function __invoke(Whereable $query): void
    {
        foreach ($this->filters as [$column, $operator, $value]) {
            if ($this->type === CompositeExpression::TYPE_AND) {
                $query->where($column, $operator, $value);
            } elseif ($this->type === CompositeExpression::TYPE_OR) {
                $query->orWhere($column, $operator, $value);
            }
        }
    }
}
