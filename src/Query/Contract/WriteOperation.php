<?php

namespace Bdf\Prime\Query\Contract;

use Attribute;

/**
 * Mark a method as write operation
 * The method will execute a query which can cause modifications
 */
#[Attribute(Attribute::TARGET_METHOD)]
class WriteOperation
{
}
