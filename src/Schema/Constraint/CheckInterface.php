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
     * Depends of the SGBD
     *
     * @return mixed
     */
    public function expression();
}
