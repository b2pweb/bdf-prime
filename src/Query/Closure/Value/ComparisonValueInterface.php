<?php

namespace Bdf\Prime\Query\Closure\Value;

use Closure;
use ReflectionFunction;

/**
 * Wrap right value of comparison expression
 */
interface ComparisonValueInterface
{
    /**
     * Extract the value
     *
     * @param ReflectionFunction $reflection The closure reflection. Use {@see ReflectionFunction::getStaticVariables()} to get bound variables
     * @return mixed
     */
    public function get(ReflectionFunction $reflection);
}
