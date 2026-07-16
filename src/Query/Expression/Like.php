<?php

namespace Bdf\Prime\Query\Expression;

use Bdf\Prime\Types\ArrayType;

/**
 * Generate LIKE pattern on expressions
 *
 * <code>
 * $query->where('roles', (new Like([1, 2]))->searchableArray()); // roles LIKE '%,1,%' OR roles LIKE '%,2,%'
 * $query->where('name', (new Like('John'))->contains()); // name LIKE '%John%'
 * $query->where('name', (new Like('John'))->startsWith()); // name LIKE 'John%'
 * </code>
 */
final class Like extends AbstractExpressionTransformer
{
    private string $start = '';
    private string $end = '';
    private bool $escape = false;


    /**
     * Set the starting string
     *
     * @param string $start
     *
     * @return $this
     */
    public function start(string $start = '%'): self
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Set the ending string
     *
     * @param string $end
     *
     * @return $this
     */
    public function end(string $end = '%'): self
    {
        $this->end = $end;

        return $this;
    }

    /**
     * Enclose the pattern
     *
     * @param string $char
     *
     * @return $this
     */
    public function enclose(string $char = '%'): self
    {
        $this->start = $char;
        $this->end   = $char;

        return $this;
    }

    /**
     * Escape the value
     *
     * @param bool $escape
     *
     * @return $this
     */
    public function escape(bool $escape = true): self
    {
        $this->escape = $escape;

        return $this;
    }

    /**
     * Search attributes that contains the value
     *
     * @return $this
     */
    public function contains(): self
    {
        return $this->enclose('%');
    }

    /**
     * Search attributes that starts with the value
     *
     * @return $this
     */
    public function startsWith(): self
    {
        return $this->end('%');
    }

    /**
     * Search attributes that ends with the value
     *
     * @return $this
     */
    public function endsWith(): self
    {
        return $this->start('%');
    }

    /**
     * Perform LIKE query on @see ArrayType attribute
     *
     * @return $this
     */
    public function searchableArray(): self
    {
        $this->start = '%,';
        $this->end   = ',%';

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(): string|array
    {
        if (is_array($this->value)) {
            return array_map($this->generate(...), $this->value);
        }

        return $this->generate($this->value);
    }

    /**
     * {@inheritdoc}
     */
    public function getOperator(): string
    {
        return ':like';
    }

    /**
     * Generate the LIKE pattern
     *
     * @param string $value
     *
     * @return string
     */
    public function generate(string $value): string
    {
        if ($this->escape) {
            $value = addcslashes($value, '%_');
        }

        return $this->start.$value.$this->end;
    }
}
