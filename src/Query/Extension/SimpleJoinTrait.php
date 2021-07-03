<?php

namespace Bdf\Prime\Query\Extension;

use Bdf\Prime\Query\Clause;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Contract\Joinable;
use Bdf\Prime\Query\JoinClause;

/**
 * Trait for join() method
 *
 * @see Joinable
 * @psalm-require-implements Joinable
 *
 * @property CompilerInterface $compiler
 */
trait SimpleJoinTrait
{
    /**
     * {@inheritdoc}
     *
     * @see Joinable::join()
     */
    public function join($table, $key, ?string $operator = null, $foreign = null, string $type = Joinable::INNER_JOIN)
    {
        $this->compilerState->invalidate('joins');

        $alias = null;
        if (is_array($table)) {
            $alias = $table[1];
            $table = $table[0];
        }

        $join = new JoinClause();

        if (is_callable($key)) {
            $key($join);
        } else {
            $join->on($key, $operator, $foreign);
        }

        $this->addStatement('joins', [
            'type'  => strtoupper($type), // TODO remove strtoupper
            'table' => $table,
            'alias' => $alias,
            'on'    => $join->clauses(),
        ]);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see Joinable::leftJoin()
     */
    public function leftJoin($table, $key, ?string $operator = null, $foreign = null)
    {
        return $this->join($table, $key, $operator, $foreign, Joinable::LEFT_JOIN);
    }

    /**
     * {@inheritdoc}
     *
     * @see Joinable::rightJoin()
     */
    public function rightJoin($table, $key, ?string $operator = null, $foreign = null)
    {
        return $this->join($table, $key, $operator, $foreign, Joinable::RIGHT_JOIN);
    }

    /**
     * {@inheritdoc}
     *
     * @see Clause::addStatement()
     */
    abstract public function addStatement(string $name, $values): void;
}
