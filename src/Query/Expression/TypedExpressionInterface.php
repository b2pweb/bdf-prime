<?php

namespace Bdf\Prime\Query\Expression;

use Bdf\Prime\Types\TypeInterface;

/**
 * Interface for Expression which depends of the type
 */
interface TypedExpressionInterface
{
    /**
     * Set the expression type
     *
     * @param TypeInterface $type
     *
     * @return $this
     * @internal Should be called by the Preprocessor
     */
    public function setType(TypeInterface $type);
}
