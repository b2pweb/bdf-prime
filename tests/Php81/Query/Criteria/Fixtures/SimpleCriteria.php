<?php

namespace Php81\Query\Criteria\Fixtures;

use Bdf\Prime\Query\Criteria\Criterion;
use Bdf\Prime\Query\Criteria\CustomCriteria;

class SimpleCriteria extends CustomCriteria
{
    #[Criterion]
    public string $name;

    #[Criterion(operator: '>=')]
    public int $value;

    /**
     * @param string $name
     * @param int $value
     */
    public function __construct(string $name, int $value)
    {
        $this->name = $name;
        $this->value = $value;
    }
}
