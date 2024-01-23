<?php

namespace Bdf\Prime\Query\Expression\Json;

use Bdf\Prime\Query\Expression\ExpressionInterface;

/**
 * Check if a JSON document is valid
 * This expression will generate the JSON_VALID() function
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
