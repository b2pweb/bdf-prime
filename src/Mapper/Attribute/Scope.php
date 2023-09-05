<?php

namespace Bdf\Prime\Mapper\Attribute;

use Attribute;
use Bdf\Prime\Mapper\Mapper;

/**
 * Mark the current method as a query scope
 *
 * These methods will be accessible from repository or query, and will act as a query method extension.
 * The method must be public or protected, and takes as parameter the query instance.
 * Any extra parameters will be passed to the scope method.
 *
 * <code>
 * class MyMapper extends Mapper
 * {
 *     #[Scope]
 *     public function customMethod(QueryInterface $query, $test) {
 *     }
 *
 *     #[Scope('alias')] // The scope will be accessible with the alias name
 *     public function myScope(QueryInterface $query, $test) {
 *     }
 *
 *     #[Scope] // Static and protected methods are also supported
 *     protected static function limitedVisibility(QueryInterface $query, $test) {
 *     }
 * }
 *
 *  $repository->customMethod('test');
 *  $repository->alias('test');
 *  $repository->limitedVisibility('test');
 * </code>
 *
 * @see Mapper::scopes()
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class Scope
{
    private ?string $name;

    /**
     * @param string|null $name The scope name. If null, the method name will be used.
     */
    public function __construct(?string $name = null)
    {
        $this->name = $name;
    }

    /**
     * Get the scope name
     * If null, the method name will be used
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->name;
    }
}
