<?php

namespace Bdf\Prime\Query\Extension;

use Bdf\Prime\Query\Compiler\CompilerState;
use Bdf\Prime\Query\Contract\Projectionable;

/**
 * Trait for @see Projectionable
 *
 * @property CompilerState $compilerState
 * @property array $statements
 *
 * @psalm-require-implements Projectionable
 */
trait ProjectionableTrait
{
    /**
     * @see Projectionable::project()
     */
    public function project($columns = null)
    {
        return $this->select($columns);
    }

    /**
     * @see Projectionable::select()
     */
    public function select($columns = null)
    {
        $this->statements['columns'] = [];

        return $this->addSelect($columns);
    }

    /**
     * @see Projectionable::addSelect()
     */
    public function addSelect($columns)
    {
        $this->compilerState->invalidate('columns');

        if ($columns === null) {
            return $this;
        }

        $columns = is_array($columns) ? $columns : func_get_args();

        if ($columns === ['*']) {
            return $this;
        }

        foreach ($columns as $alias => $column) {
            $this->statements['columns'][] = [
                'column' => $column,
                'alias'  => is_int($alias) ? null : $alias,
            ];
        }

        return $this;
    }
}
