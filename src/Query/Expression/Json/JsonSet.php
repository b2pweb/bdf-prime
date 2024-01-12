<?php

namespace Bdf\Prime\Query\Expression\Json;

use Bdf\Prime\Query\Expression\ExpressionInterface;

/**
 * Set a JSON field into a JSON document, and return the new JSON document
 *
 * If the field does not exist, it will be created
 * If the field already exists, it will be replaced
 *
 * This expression will generate the JSON_SET() function
 *
 * @see JsonInsert for insert a value only if the field does not exist
 * @see JsonReplace for replace a value only if the field exists
 */
final class JsonSet extends JsonFunction
{
    /**
     * @param ExpressionInterface|string $document The JSON document to update. Can be an attribute name, or a SQL expression.
     * @param string $path The path to the field to update. Should start with '$' which is the root of the JSON document.
     * @param mixed $value The PHP value to set. This value will be converted to json.
     */
    public function __construct($document, string $path, $value)
    {
        parent::__construct('JSON_SET', $document, $path, new ToJson($value));
    }
}
