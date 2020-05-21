<?php

namespace Bdf\Prime\Schema;

use Bdf\Prime\Schema\Constraint\ConstraintVisitorInterface;

/**
 * Interface for database schema constraints
 */
interface ConstraintInterface
{
    /**
     * Get the constraint name
     *
     * @return string
     */
    public function name();

    /**
     * Visit the constraint object
     *
     * @param ConstraintVisitorInterface $visitor
     *
     * @return void
     */
    public function visit(ConstraintVisitorInterface $visitor);
}
