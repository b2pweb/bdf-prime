<?php

namespace Bdf\Prime\Schema\Constraint;

use Bdf\Prime\Schema\ConstraintInterface;

/**
 * CHECK constraint
 */
interface CheckInterface extends ConstraintInterface
{
    /**
     * Get the check expression
     * Depends on the SGBD
     *
     * @return mixed
     */
    public function expression();
}
