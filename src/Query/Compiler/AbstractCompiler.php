<?php

namespace Bdf\Prime\Query\Compiler;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Query\CompilableClause;

/**
 * Base class for create compilers
 *
 * - Implements doCompile* methods for doing compilation, without take care of side effects
 * - Check $this->isCompiling() on reset() method
 * - Use preprocessor->field() for compiling update and insert values, or aggregation projection, group, order columns
 * - For compile projection (SELECT columns), use preprocessor->root() for select all columns
 * - preprocessor->table() for register new tables / relations (FROM & JOIN)
 * - Use preprocessor->expression() for compile filter (WHERE, ON, HAVING) expression
 */
abstract class AbstractCompiler implements CompilerInterface
{
    /**
     * The connection platform
     *
     * @var ConnectionInterface
     */
    protected $connection;


    /**
     * AbstractCompiler constructor.
     *
     * @param ConnectionInterface $connection
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function platform()
    {
        return $this->connection->platform();
    }

    /**
     * {@inheritdoc}
     */
    public function compileInsert(CompilableClause $query)
    {
        try {
            $query->state()->compiling = true;
            $query = $query->preprocessor()->forInsert($query);

            return $this->doCompileInsert($query);
        } finally {
            $query->state()->compiling = false;
            $query->preprocessor()->clear();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function compileUpdate(CompilableClause $query)
    {
        try {
            $query->state()->compiling = true;
            $query = $query->preprocessor()->forUpdate($query);

            return $this->doCompileUpdate($query);
        } finally {
            $query->state()->compiling = false;
            $query->preprocessor()->clear();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function compileDelete(CompilableClause $query)
    {
        try {
            $query->state()->compiling = true;
            $query = $query->preprocessor()->forDelete($query);

            return $this->doCompileDelete($query);
        } finally {
            $query->state()->compiling = false;
            $query->preprocessor()->clear();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function compileSelect(CompilableClause $query)
    {
        try {
            $query->state()->compiling = true;
            $query = $query->preprocessor()->forSelect($query);

            return $this->doCompileSelect($query);
        } finally {
            $query->state()->compiling = false;
            $query->preprocessor()->clear();
        }
    }

    /**
     * Try to resolve type and auto convert value
     *
     * @param mixed $value
     *
     * @return mixed
     */
    protected function autoConvertValue($value)
    {
        if ($value === null) {
            return null;
        }

        return $this->platform()->types()->toDatabase($value);
    }

    /**
     * Try to resolve type and auto convert values.
     *
     * @param mixed $values If is array, convert each values, else convert the value
     *
     * @return mixed
     *
     * @see AbstractCompiler::autoConvertValue()
     */
    protected function autoConvertValues($values)
    {
        if (!is_array($values)) {
            return $this->autoConvertValue($values);
        }

        foreach ($values as &$e) {
            $e = $this->autoConvertValue($e);
        }

        return $values;
    }

    /**
     * Compile an insert query
     *
     * @param CompilableClause $query
     *
     * @return mixed
     */
    abstract protected function doCompileInsert(CompilableClause $query);

    /**
     * Compile an update query
     *
     * @param CompilableClause $query
     *
     * @return mixed
     */
    abstract protected function doCompileUpdate(CompilableClause $query);

    /**
     * Compile a delete query
     *
     * @param CompilableClause $query
     *
     * @return mixed
     */
    abstract protected function doCompileDelete(CompilableClause $query);

    /**
     * Compile a select query
     *
     * @param CompilableClause $query
     *
     * @return mixed
     */
    abstract protected function doCompileSelect(CompilableClause $query);
}
