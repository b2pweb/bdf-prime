<?php

namespace Bdf\Prime\Query;

use Doctrine\DBAL\Query\Expression\CompositeExpression;

use function explode;
use function is_int;
use function is_iterable;
use function is_string;
use function trim;

/**
 * Clause
 *
 * @author seb
 */
class Clause implements ClauseInterface
{
    /**
     * The collection of custom filter.
     *
     * @var array<string,callable(static,mixed):void>
     */
    protected $customFilters = [];

    /**
     * The clause statements
     *
     * @var array<string,mixed>
     */
    public $statements = [];

    /**
     * Available operators
     *
     * @var array<string, true>
     */
    protected $operators = [
        '<'             => true,
        ':lt'           => true,
        '<='            => true,
        ':lte'          => true,
        '>'             => true,
        ':gt'           => true,
        '>='            => true,
        ':gte'          => true,
        '~='            => true,
        '=~'            => true,
        ':regex'        => true,
        ':like'         => true,
        'in'            => true,
        ':in'           => true,
        'notin'         => true,
        '!in'           => true,
        ':notin'        => true,
        'between'       => true,
        ':between'      => true,
        '!between'      => true,
        ':notbetween'   => true,
        '<>'            => true,
        '!='            => true,
        ':ne'           => true,
        ':not'          => true,
        '='             => true,
        ':eq'           => true,
    ];

    /**
     * {@inheritdoc}
     */
    public function setCustomFilters(array $filters)
    {
        $this->customFilters = $filters;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addCustomFilter(string $name, callable $callback)
    {
        $this->customFilters[$name] = $callback;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomFilters(): array
    {
        return $this->customFilters;
    }

    /**
     * {@inheritdoc}
     */
    public function statement(string $statement): array
    {
        return $this->statements[$statement] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function addStatement(string $name, $values): void
    {
        $this->statements[$name][] = $values;
    }

    /**
     * {@inheritdoc}
     */
    public function buildClause(string $statement, $expression, $operator = null, $value = null, string $type = CompositeExpression::TYPE_AND)
    {
        if (is_iterable($expression)) {
            //nested expression
            $glue = ($operator ?: CompositeExpression::TYPE_AND);
            $parts = [];

            foreach ($expression as $key => $value) {
                if (isset($this->customFilters[$key])) {
                    // Custom filter
                    $this->customFilters[$key]($this, $value);
                } elseif (is_int($key)) {
                    // Raw value
                    $this->buildRaw($statement, $value, $glue);
                } elseif ($key[0] === ':') {
                    // Special command
                    $this->addCommand($key, $value);
                } else {
                    // Column with operator
                    $key = explode(' ', trim($key), 2);
                    $parts[] = [
                        'column'    => $key[0],
                        'operator'  => $key[1] ?? '=',
                        'value'     => $value,
                        'glue'      => $glue,
                    ];
                }
            }

            if ($parts) {
                $this->statements[$statement][] = [
                    'nested'  => $parts,
                    'glue'    => $type,
                ];
            }
        } else {
            //if no value. Check if operator is a value. Otherwise we assume it is a 'is null' request
            if ($value === null && (!is_string($operator) || !isset($this->operators[$operator]))) {
                $value = $operator;
                $operator = '=';
            }

            if (isset($this->customFilters[$expression])) {
                // Custom filter
                $this->customFilters[$expression]($this, $value);
            } else {
                // Column with operator
                $this->statements[$statement][] = [
                    'column'    => $expression,
                    'operator'  => $operator,
                    'value'     => $value,
                    'glue'      => $type,
                ];
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRaw(string $statement, $expression, string $type = CompositeExpression::TYPE_AND)
    {
        $this->statements[$statement][] = [
            'raw'  => $expression,
            'glue' => $type,
        ];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function buildNested(string $statement, callable $callback, string $type = CompositeExpression::TYPE_AND)
    {
        $statements = $this->statements;
        $this->statements = [];

        $callback($this);

        if (!empty($this->statements[$statement])) {
            $statements[$statement][] = [
                'nested' => $this->statements[$statement],
                'glue'   => $type,
            ];
        }

        $this->statements = $statements;

        return $this;
    }

    /**
     * @todo Revoir cette gestion des commandes
     * {@inheritdoc}
     */
    public function addCommand(string $command, $value)
    {
        // TO overload

        return $this;
    }
}
