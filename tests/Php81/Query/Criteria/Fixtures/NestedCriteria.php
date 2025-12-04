<?php

namespace Php81\Query\Criteria\Fixtures;

use Bdf\Prime\Query\Criteria\Criterion;
use Bdf\Prime\Query\Criteria\CustomCriteria;
use Bdf\Prime\Query\Criteria\StartsWithCriterion;
use DateTime;

class NestedCriteria extends CustomCriteria
{
    #[Criterion]
    public NameSearchCriteria $name;

    #[Criterion(field: 'date', operator: '>=')]
    public DateTime $after;

    public function __construct(string $name, DateTime $after)
    {
        $this->name = new NameSearchCriteria($name, $name);
        $this->after = $after;
    }
}

class NameSearchCriteria extends CustomCriteria
{
    protected const SEPARATOR = 'OR';

    #[StartsWithCriterion]
    public string $firstName;

    #[StartsWithCriterion]
    public string $lastName;

    public function __construct(string $firstName, string $lastName)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }
}
