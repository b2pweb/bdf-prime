<?php

namespace Bdf\Prime\Query\Extension;

use Bdf\Prime\Query\Clause;
use Bdf\Prime\Query\Contract\Joinable;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\JoinClause;
use Closure;

/**
 * Trait for join() method
 *
 * @see Joinable
 *
 * @property CompilerInterface $compiler
 */
trait SimpleJoinTrait
{
    /**
     * @see Joinable::join()
     */
    public function join($table, $key, $operator = null, $foreign = null, $type = Joinable::INNER_JOIN)
    {
        $this->compilerState->invalidate('joins');

        $alias = null;
        if (is_array($table)) {
            $alias = $table[1];
            $table = $table[0];
        }

        $join = new JoinClause();

        if ($key instanceof Closure) {
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
     * @see Joinable::leftJoin()
     */
    public function leftJoin($fromAlias, $join, $alias, $condition = null)
    {
        return $this->join($fromAlias, $join, $alias, $condition, Joinable::LEFT_JOIN);
    }

    /**
     * @see Joinable::rightJoin()
     */
    public function rightJoin($fromAlias, $join, $alias, $condition = null)
    {
        return $this->join($fromAlias, $join, $alias, $condition, Joinable::RIGHT_JOIN);
    }

    /**
     * @see Clause::addStatement()
     */
    abstract public function addStatement($name, $values);
}
