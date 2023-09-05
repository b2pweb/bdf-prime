<?php

namespace Bdf\Prime\Mapper\Attribute;

use Attribute;
use Bdf\Prime\Mapper\Mapper;

/**
 * Mark the current method as a query filter
 *
 * These methods will accessible as query filter, on "column" parameter of where() method.
 *
 * The method must :
 * - be public or protected
 * - takes as first parameter the query instance
 * - takes as second parameter the filter parameter
 * - return nothing
 *
 * <code>
 * class MyMapper extends Mapper
 * {
 *     #[Filter]
 *     public function customFilter(QueryInterface $query, $test) {
 *     }
 *
 *     #[Filter('alias')] // The filter will be accessible with the alias name
 *     public function myFilter(QueryInterface $query, $test) {
 *     }
 *
 *     #[Filter] // Static and protected methods are also supported
 *     protected static function limitedVisibility(QueryInterface $query, $test) {
 *     }
 * }
 *
 * $repository->where('customMethod', 'test');
 * $repository->where('alias', 'test');
 * $repository->where('limitedVisibility', 'test');
 * </code>
 *
 * @see Mapper::filters()
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Filter
{
    private ?string $name;

    /**
     * @param string|null $name The filter name. If null, the method name will be used.
     */
    public function __construct(?string $name = null)
    {
        $this->name = $name;
    }

    /**
     * Get the filter name
     * If null, the method name will be used
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->name;
    }
}
