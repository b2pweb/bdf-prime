<?php

namespace Php81\Query\Criteria\Fixtures;

use Bdf\Prime\Query\Criteria\CustomCriteria;
use Bdf\Prime\Query\Criteria\LikeCriterion;
use Bdf\Prime\Query\Criteria\StartsWithCriterion;

class WithLikeCriteria extends CustomCriteria
{
    #[StartsWithCriterion]
    public string $name;

    #[LikeCriterion(field: 'email', start: '%@')]
    public string $domain;

    /**
     * @param string $name
     * @param string $domain
     */
    public function __construct(string $name, string $domain)
    {
        $this->name = $name;
        $this->domain = $domain;
    }
}
