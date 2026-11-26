<?php

namespace Php81\Query\Criteria\Fixtures;

use Bdf\Prime\Query\Criteria\CustomCriteria;
use Bdf\Prime\Query\Criteria\LikeCriterion;
use Bdf\Prime\Query\Criteria\StartsWithCriterion;

class WithLikeCriteriaArray extends CustomCriteria
{
    #[StartsWithCriterion]
    public string $name;

    #[LikeCriterion(field: 'email', start: '%@')]
    public array $domains;

    /**
     * @param string $name
     * @param list<string> $domains
     */
    public function __construct(string $name, array $domains)
    {
        $this->name = $name;
        $this->domains = $domains;
    }
}
