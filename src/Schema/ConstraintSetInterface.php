<?php

namespace Bdf\Prime\Schema;

use Bdf\Prime\Schema\Constraint\ConstraintVisitorInterface;

/**
 * Set of @see ConstraintInterface
 */
interface ConstraintSetInterface
{
    /**
     * Apply the visitor on each constraints
     *
     * @param ConstraintVisitorInterface $visitor
     *
     * @return $this
     */
    public function apply(ConstraintVisitorInterface $visitor);

    /**
     * Get all constraints
     *
     * @return ConstraintInterface[]
     */
    public function all();

    /**
     * Get a constraint by its name
     *
     * @param string $name
     *
     * @return ConstraintInterface
     */
    public function get($name);
}
