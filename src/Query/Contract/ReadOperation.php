<?php

namespace Bdf\Prime\Query\Contract;

use Attribute;

/**
 * Mark a query method as read operation
 * The method will execute a query without perform change on database
 */
#[Attribute(Attribute::TARGET_METHOD)]
class ReadOperation
{
}
