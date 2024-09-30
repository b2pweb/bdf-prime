<?php

namespace Bdf\Prime\Mapper\Attribute;

use Attribute;
use Bdf\Prime\Mapper\Mapper;

/**
 * Mark the current method as a repository method extension
 *
 * These methods will be accessible from repository, and will be used to call or create custom queries.
 * The method must be public or protected, and takes as parameter the repository.
 * Any extra parameters will be passed to the method.
 *
 * A custom query works mostly like scopes, but with some differences :
 * - Cannot be called using a query (i.e. $query->where(...)->myScope())
 * - The function has responsibility of creating the query instance
 * - The first argument is the repository
 *
 * <code>
 * class MyMapper extends Mapper
 * {
 *     #[RepositoryMethod]
 *     public function findByCustom(EntityRepository $repository, $search) {
 *        return $repository->make(MyCustomQuery::class)->where('first', $search)->first();
 *     }
 *
 *     #[Scope('alias')] // The method will be accessible with the alias name
 *     public function myCustomMethod(EntityRepository $repository, $test) {
 *     }
 *
 *     #[Scope] // Static and protected methods are also supported
 *     protected static function limitedVisibility(EntityRepository $repository, $test) {
 *     }
 * }
 *
 *  $repository->findByCustom('test');
 *  $repository->alias('test');
 *  $repository->limitedVisibility('test');
 * </code>
 *
 * @see Mapper::queries()
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class RepositoryMethod
{
    private ?string $name;
    private bool $jit = false;

    /**
     * @param string|null $name The repository method name. If null, the actual method name will be used.
     * @param bool $jit Enable the JIT compilation for this method. If true, the queries of this method will be compiled on the fly, if possible.
     */
    public function __construct(?string $name = null, bool $jit = false)
    {
        $this->name = $name;
        $this->jit = $jit;
    }

    /**
     * Get the repository method name
     * If null, the actual method name will be used
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->name;
    }

    /**
     * Check if the JIT compilation is enabled for this method
     * If true, the queries of this method will be compiled on the fly, if possible
     *
     * @return bool
     */
    public function jit(): bool
    {
        return $this->jit;
    }
}
