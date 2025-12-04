<?php

namespace Php81\Query\Criteria\Fixtures;

use Bdf\Prime\Query\Criteria\Criterion;
use Bdf\Prime\Query\Criteria\CustomCriteria;
use Bdf\Prime\Query\Expression\Attribute;
use Bdf\Prime\Query\Expression\Json\JsonExtract;

class WithLeftExpressionCriteria extends CustomCriteria
{
    #[Criterion(field: new JsonExtract('metadata', 'tag'), operator: '>=')]
    public int $value;

    #[Criterion(field: new Attribute('content', 'MD5(%s)'))]
    public string $hash;

    /**
     * @param int $value
     * @param string $hash
     */
    public function __construct(int $value, string $hash)
    {
        $this->value = $value;
        $this->hash = $hash;
    }
}
