<?php

namespace Bdf\Prime\Query\Expression\Json;

use Bdf\Prime\Query\Expression\ExpressionInterface;

/**
 * Insert a new JSON field into a JSON document, and return the new JSON document
 *
 * If the field does not exist, it will be created
 * If the field already exists, it will do nothing
 *
 * This expression will generate the JSON_REPLACE() function
 *
 * @see JsonSet for perform upsert operation
 * @see JsonReplace for replace a value only if the field exists
 *
 * @template Q as \Bdf\Prime\Query\CompilableClause&\Bdf\Prime\Query\Contract\Compilable
 * @template C as object
 *
 * @extends JsonFunction<Q, C>
 */
final class JsonInsert extends JsonFunction
{
    /**
     * @param ExpressionInterface|string $document The JSON document to update. Can be an attribute name, or a SQL expression.
     * @param string $path The path to the field to add. Should start with '$' which is the root of the JSON document.
     * @param mixed $value The PHP value to set. This value will be converted to json.
     */
    public function __construct($document, string $path, $value)
    {
        parent::__construct('JSON_INSERT', $document, $path, new ToJson($value));
    }
}
