<?php

namespace Bdf\Prime\Query\Compiler\AliasResolver;

use Bdf\Prime\Mapper\Metadata;

/**
 * ExpressionExplorationState
 */
final class ExpressionExplorationState
{
    public ?string $alias = null;
    public string $path = '';
    public Metadata $metadata;
    public ?string $attribute = null;
}
