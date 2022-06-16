<?php

namespace Bdf\Prime\Query\Compiler\AliasResolver;

/**
 * ExpressionToken
 */
class ExpressionToken
{
    public const TYPE_DYN   = 0;
    public const TYPE_STA   = 1;
    public const TYPE_ATTR  = 2;
    public const TYPE_ALIAS = 3;

    public $type;
    public $value;

    /**
     * ExpressionToken constructor.
     *
     * @param $type
     * @param $value
     */
    public function __construct($type, $value)
    {
        $this->type = $type;
        $this->value = $value;
    }
}
