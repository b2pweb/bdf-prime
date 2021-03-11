<?php

namespace Bdf\Prime\Query\Contract\Query;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Contract\Aggregatable;
use Bdf\Prime\Query\Contract\Deletable;
use Bdf\Prime\Query\Contract\Projectionable;
use Bdf\Prime\Query\ReadCommandInterface;

/**
 * Query for perform simple key / value search
 *
 * This query can only perform "equals" comparison, with "AND" combination only on the current table (no relation resolve)
 *
 * <code>
 * $query
 *     ->from('test_')
 *     ->where('foo', 'bar')
 *     ->first()
 * ;
 * </code>
 */
interface KeyValueQueryInterface extends ReadCommandInterface, Projectionable, Aggregatable, Deletable
{
    /**
     * Select the table where query must be performed
     *
     * @param string $from The table.
     * @param string|null $alias Not in use : For compatibilty with CommandInterface::from()
     *
     * @return $this This Query instance.
     */
    public function from($from, $alias = null);

    /**
     * Filter fields
     * Valid filters are only strict equality on table attributes
     *
     * <code>
     * // Builder syntax
     * $query
     *     ->from('users')
     *     ->where('name', 'Jean')
     *     ->where('email', 'jean@b2pweb.com')
     *     ->all()
     * ;
     *
     * // Array critieria syntax
     * $query
     *     ->from('users')
     *     ->where([
     *         'name'  => 'Jean',
     *         'email' => 'jean@b2pweb.com'
     *     ])
     *     ->all()
     * ;
     * </code>
     *
     * /!\ NULL is not supported by SQL connections
     *
     * @param string|array<string,mixed> $field The field name, or list of fields, as array in form [field name] => [field value]
     * @param mixed $value The field value. This parameter is ignored when first parameter is an array
     *
     * @return $this
     */
    public function where($field, $value = null);

    /**
     * Set new values for UPDATE operation
     *
     * <code>
     * $query
     *     ->from('users')
     *     ->where('id', 1)
     *     ->values(
     *         [
     *             'name'  => 'Paul',
     *             'roles' => [1, 3]
     *         ],
     *         ['roles' => 'array']
     *     )
     *     ->update();
     * </code>
     *
     * @param array<string,mixed> $values Values to update in form [attribute] => [value]
     * @param array $types Define attributes types in form [attribute] => [value type]. If not provided the type will be resolved from value or mapper
     *
     * @return $this
     */
    public function values(array $values = [], array $types = []);

    /**
     * Peform the UPDATE operation
     *
     * @param null|array $values The vlaue to set, if provided
     *
     * @todo Interface for update operations
     *
     * @return int Number of affected rows
     *
     * @see KeyValueQueryInterface::values() For set value (internally used by $values parameters)
     * @throws PrimeException When execute fail
     */
    public function update($values = null);

    /**
     * {@inheritdoc}
     */
    public function execute($columns = null);
}
