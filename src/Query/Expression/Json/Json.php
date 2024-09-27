<?php

namespace Bdf\Prime\Query\Expression\Json;

use BadMethodCallException;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Expression\ExpressionInterface;

/**
 * Facade class for SQL JSON functions and expressions
 *
 * This class is used to build JSON path expression, and JSON functions
 * By default, this expression will generates a JSON_EXTRACT() expression using internally {@see JsonExtract}
 *
 * Note: this class is immutable, so all methods will return a new instance
 *
 * Usage:
 * <code>
 *     // Extract a value from a JSON field
 *     // Here the SQL will be like `SELECT * FROM users WHERE options->>'$.theme.name' = 'christmas'`
 *     User::repository()->where(Json::attr('options')->theme->name, 'christmas')->all();
 *
 *     // Array access path can also be generated
 *     // Generates: `SELECT * FROM users WHERE roles->>'$[0]' = 'admin'`
 *     User::repository()->where(Json::attr('roles')[0], 'admin')->all();
 *
 *     // Build a "json contains" expression
 *     // Generates: `SELECT * FROM users WHERE JSON_CONTAINS(options->'$.flags', '"bot"')`
 *     User::repository()->whereRaw(Json::attr('options')->flags->contains('bot'))->all();
 *
 *     // Build a "json contains path" expression
 *     // Generates: `SELECT * FROM users WHERE JSON_CONTAINS_PATH(options, 'all', '$.flags.bot')`
 *     User::repository()->whereRaw(Json::attr('options')->flags->hasPath('bot'))->all();
 *
 *     // Update a json document
 *     // Generates: `UPDATE users SET options = JSON_SET(options, '$.theme', '"christmas"') WHERE options->>'$.theme' IS NULL`
 *     User::repository()
 *         ->whereNull(Json::attr('options')->theme))
 *         ->set('options', Json::attr('options')->theme->set('christmas'))
 *         ->update()
 *     ;
 * </code>
 *
 * @template Q as \Bdf\Prime\Query\CompilableClause&\Bdf\Prime\Query\Contract\Compilable
 * @template C as object
 *
 * @implements ExpressionInterface<Q, C>
 * @implements \ArrayAccess<array-key, Json>
 */
final class Json implements ExpressionInterface, \ArrayAccess
{
    private string $field;
    private string $path;
    private bool $unquote = true;

    /**
     * @param string $field
     * @param string $path
     */
    public function __construct(string $field, string $path = '$')
    {
        $this->field = $field;
        $this->path = $path;
    }

    /**
     * Unquote the JSON result
     *
     * When unquote is enable, a string value will be returned as a string, and not as a JSON string.
     * If the value is not a string, the result is unchanged.
     *
     * This method will not modify the current instance, but return a new instance.
     *
     * @param bool $unquote
     *
     * @return self The new instance
     */
    public function unquote(bool $unquote = true): self
    {
        if ($this->unquote === $unquote) {
            return $this;
        }

        $self = clone $this;
        $self->unquote = $unquote;

        return $self;
    }

    /**
     * Add to the JSON path expression
     *
     * This method will not modify the current instance, but return a new instance.
     *
     * @param string $path The path to add
     *
     * @return self The new instance
     */
    public function addPath(string $path): self
    {
        $self = clone $this;
        $self->path .= $path;

        return $self;
    }

    /**
     * Check if the JSON contains the given value
     *
     * This method will build a JSON_CONTAINS() expression
     *
     * @param scalar $value Value to search
     *
     * @return JsonContains
     */
    public function contains($value): JsonContains
    {
        // JSON_CONTAINS() does not support unquoted values, so we need to disable unquote
        $self = $this->unquote(false);

        return new JsonContains($self, $value);
    }

    /**
     * Check if the JSON contains the given path
     *
     * This method will build a JSON_CONTAINS_PATH() expression
     *
     * Usage:
     * <code>
     *     Json::attr('options')->hasPath('theme'); // Check if the JSON contains the "theme" field
     *     Json::attr('options')->theme->hasPath(); // Same as above
     *     Json::attr('options')->theme->hasPath('flags'); // Check for field "theme.flags"
     *     Json::attr('options')->themes->hasPath('[1]'); // Check for field "themes[1]"
     * </code>
     *
     * @param string|null $path The path to search. If null, the current path will be used, otherwise the given path will be appended to the current path
     *
     * @return JsonContainsPath
     */
    public function hasPath(?string $path = null): JsonContainsPath
    {
        $fullPath = $this->path;

        if ($path !== null) {
            if ($path[0] === '.' || $path[0] === '[') {
                $fullPath .= $path;
            } else {
                $fullPath .= '.' . $path;
            }
        }

        return new JsonContainsPath($this->field, $fullPath);
    }

    /**
     * Modify the JSON document of the field, and set the new value into the given path
     * The generated expression will return a new JSON document
     *
     * The field will be created if it does not exist, and replaced otherwise
     *
     * @param mixed $value The new value to set. It will be converted to JSON.
     *
     * @return JsonSet
     */
    public function set($value): JsonSet
    {
        return new JsonSet($this->field, $this->path, $value);
    }

    /**
     * Modify the JSON document of the field, and add a new value into the given path, if the field does not exist
     * The generated expression will return a new JSON document
     *
     * @param mixed $value The new value to set. It will be converted to JSON.
     *
     * @return JsonInsert
     */
    public function insert($value): JsonInsert
    {
        return new JsonInsert($this->field, $this->path, $value);
    }

    /**
     * Modify the JSON document of the field, and replace the value into the given path, if the field exists
     * The generated expression will return a new JSON document
     *
     * If the field does not exist, the expression will do nothing
     *
     * @param mixed $value The new value to set. It will be converted to JSON.
     *
     * @return JsonReplace
     */
    public function replace($value): JsonReplace
    {
        return new JsonReplace($this->field, $this->path, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function build(CompilableClause $query, object $compiler): string
    {
        return (new JsonExtract($this->field, $this->path, $this->unquote))->build($query, $compiler);
    }

    /**
     * Add a field name to the JSON path
     *
     * This method will not modify the current instance, but return a new instance.
     *
     * @param string $name The field name
     *
     * @return self The new instance
     */
    public function __get(string $name): self
    {
        return $this->addPath('.' . $name);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        throw new BadMethodCallException(self::class . ' is not an array and implements only offer get for build JSON path');
    }

    /**
     * Add an array access to the JSON path
     *
     * This method will not modify the current instance, but return a new instance.
     *
     * @param int|string $offset
     *
     * @return self The new instance
     */
    public function offsetGet($offset): self
    {
        return $this->addPath('[' . $offset . ']');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
        throw new BadMethodCallException(self::class . ' is not an array and implements only offer get for build JSON path');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        throw new BadMethodCallException(self::class . ' is not an array and implements only offer get for build JSON path');
    }

    /**
     * Start a JSON expression from the given field
     *
     * @param string $attribute The field name
     *
     * @return self
     */
    public static function attr(string $attribute): self
    {
        return new self($attribute);
    }

    /**
     * Create SQL expression for check if a JSON document is valid
     *
     * @param string|ExpressionInterface $document The value to check. Can be an attribute name, or a SQL expression.
     *
     * @return JsonValid
     */
    public static function valid($document): JsonValid
    {
        return new JsonValid($document);
    }
}
