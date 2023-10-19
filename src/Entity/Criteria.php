<?php

namespace Bdf\Prime\Entity;

use ArrayAccess;
use Bdf\Prime\Query\Contract\Orderable;
use Exception;
use IteratorAggregate;
use Traversable;

/**
 * Builder for entity criteria
 */
class Criteria implements ArrayAccess, IteratorAggregate
{
    /**
     * Critères injectés vers le dépot d'entity
     *
     * @var array<string, mixed>
     */
    protected array $criteria = [];

    /**
     * Map attribute name to filters (i.e. attribute with operator)
     *
     * <pre>
     * ex:
     *   ['weight >' => 10, 'weight <' => 20]
     * Will generates inputs:
     *   ['weight' => ['weight >', 'weight <']]
     * </pre>
     *
     * @var array<string, list<string>>
     */
    protected array $inputs = [];

    /**
     * Commandes spéciales du query builder (ex: ':limit')
     *
     * @var array<string, mixed>
     */
    protected array $specials = [];

    /**
     * Constructor
     *
     * @param array $filters
     * @psalm-consistent-constructor
     */
    public function __construct(array $filters = [])
    {
        $this->import($filters);
    }

    /**
     * Import criteria
     *
     * @param array $filters
     *
     * @return self
     */
    public function import($filters)
    {
        foreach ($filters as $filter => $value) {
            $this->add($filter, $value);
        }

        return $this;
    }

    /**
     * Add a criterion
     *
     * @param string $filter
     * @param mixed  $value
     * @param bool   $replace
     *
     * @return self
     */
    public function add($filter, $value, $replace = false)
    {
        if ($filter[0] === ':') {
            $this->specials[$filter] = $value;
        } else {
            list($attribute) = explode(' ', trim($filter));

            if ($replace && isset($this->inputs[$attribute])) {
                $this->remove($attribute);
            }

            $this->criteria[$filter]  = $value;
            $this->inputs[$attribute][] = $filter;
        }

        return $this;
    }

    /**
     * Remove criterion
     *
     * @param string $filter   criterion to remove. Could be a alias
     * @return self
     */
    public function remove($filter)
    {
        list($attribute) = explode(' ', trim($filter));

        if (isset($this->inputs[$attribute])) {
            foreach ($this->inputs[$attribute] as $key) {
                unset($this->criteria[$key]);
            }
        }

        unset($this->criteria[$filter]);
        unset($this->inputs[$attribute]);
        unset($this->specials[$filter]);

        return $this;
    }

    /**
     * Does criterion exist
     *
     * @param string $filter    criterion exists. Could be a alias
     * @return bool
     */
    public function exists($filter)
    {
        return isset($this->criteria[$filter]) || isset($this->inputs[$filter]);
    }

    /**
     * Get a criterion value
     *
     * @param string $filter    criterion to get. Could be a alias
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($filter, $default = null)
    {
        if (isset($this->criteria[$filter])) {
            return $this->criteria[$filter];
        }

        if (isset($this->inputs[$filter])) {
            return $this->criteria[$this->inputs[$filter][0]];
        }

        return $default;
    }

    /**
     * Get special criterion
     *
     * @param string $filter   Could be a alias
     * @param mixed  $default
     *
     * @return mixed
     */
    public function special($filter, $default = null)
    {
        return isset($this->specials[$filter])
            ? $this->specials[$filter]
            : $default;
    }

    /**
     * Get all criteria including specials
     *
     * @return array
     */
    public function all()
    {
        return $this->criteria + $this->specials;
    }

    /**
     * Get criteria only
     *
     * @todo trigger "Using deprecated language feature PHP4 constructor"
     *
     * @return array
     */
    public function criteria()
    {
        return $this->criteria;
    }

    /**
     * Get all special criteria
     *
     * @return array
     */
    public function specials()
    {
        return $this->specials;
    }

    /**
     * Set attribute order
     *
     * @param string $attribute
     * @param Orderable::ORDER_* $type
     *
     * @return void
     */
    public function order(string $attribute, string $type = Orderable::ORDER_ASC): void
    {
        $this->specials[':order'][$attribute] = $type;
    }

    /**
     * Get attribute order type
     *
     * @param string $attribute
     * @return string|null  Returns attribute order type. Null if not found
     */
    public function orderType($attribute)
    {
        return isset($this->specials[':order'][$attribute])
            ? $this->specials[':order'][$attribute]
            : null;
    }

    /**
     * Set/Get page number
     *
     * @param int $page
     * @return int|null
     */
    public function page($page = null)
    {
        if ($page === null) {
            return isset($this->specials[':limitPage'][0])
                ? $this->specials[':limitPage'][0]
                : 0;
        }

        $this->specials[':limitPage'][0] = $page;
    }

    /**
     * Set/Get the max number of rows in a page
     *
     * @param int $maxRows
     * @return int|null
     */
    public function pageMaxRows($maxRows = null)
    {
        if ($maxRows === null) {
            return isset($this->specials[':limitPage'][1])
                ? $this->specials[':limitPage'][1]
                : 0;
        }

        $this->specials[':limitPage'][1] = $maxRows;
    }

    /**
     * Set/Get SQL max results
     *
     * @param int $maxResults
     * @return int|null
     */
    public function maxResults($maxResults = null)
    {
        if ($maxResults === null) {
            return isset($this->specials[':limit'])
                ? $this->specials[':limit']
                : 0;
        }

        $this->specials[':limit'] = $maxResults;
    }

    /**
     * Set/Get SQL offset
     *
     * @param int $firstResult
     * @return int|null
     */
    public function firstResult($firstResult = null)
    {
        if ($firstResult === null) {
            return isset($this->specials[':offset'])
                ? $this->specials[':offset']
                : 0;
        }

        $this->specials[':offset'] = $firstResult;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
        $this->add($offset, $value);
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return $this->exists($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        $this->remove($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): Traversable
    {
        yield from $this->all();
    }
}
