<?php

namespace Php81\Query\Criteria\Fixtures;

use Bdf\Prime\Query\Criteria\Criterion;
use Bdf\Prime\Query\Criteria\CustomCriteria;

class NullableCriteria extends CustomCriteria
{
    #[Criterion]
    public ?string $foo = null;

    #[Criterion(skipNull: false)]
    public ?string $bar = null;
}
