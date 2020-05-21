<?php

namespace Bdf\Prime\Query\Compiler\AliasResolver;

use Bdf\Prime\Mapper\Metadata;

/**
 * ExpressionExplorationState
 */
class ExpressionExplorationState
{
    /**
     * @var string
     */
    public $alias;

    /**
     * @var string
     */
    public $path = '';

    /**
     * @var Metadata
     */
    public $metadata;

    /**
     * @var string
     */
    public $attribute;
}