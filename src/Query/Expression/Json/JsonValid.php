<?php

namespace Bdf\Prime\Query\Expression\Json;

use Bdf\Prime\Query\Expression\ExpressionInterface;

/**
 * Check if a JSON document is valid
 * This expression will generate the JSON_VALID() function
 *
 * @template Q as \Bdf\Prime\Query\CompilableClause&\Bdf\Prime\Query\Contract\Compilable
 * @template C as object
 *
 * @extends JsonFunction<Q, C>
 */
final class JsonValid extends JsonFunction
{
    /**
     * @param string|ExpressionInterface $document The value to check. Can be an attribute name, or a SQL expression.
     */
    public function __construct($document)
    {
        parent::__construct('JSON_VALID', $document);
    }
}
