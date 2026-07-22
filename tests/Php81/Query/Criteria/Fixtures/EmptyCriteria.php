<?php

namespace Php81\Query\Criteria\Fixtures;

use Bdf\Prime\Query\Criteria\Criterion;
use Bdf\Prime\Query\Criteria\CustomCriteria;

class EmptyCriteria extends CustomCriteria
{
    #[Criterion(skipEmpty: true)]
    public string|array|null $foo = null;

    #[Criterion(skipNull: false)]
    public ?string $bar = null;
}
