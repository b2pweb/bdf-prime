<?php

namespace Bdf\Prime\Query\Compiler;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Platform\PlatformInterface;

/**
 * Base class for create compilers
 *
 * - Implements doCompile* methods for doing compilation, without take care of side effects
 * - Check $this->isCompiling() on reset() method
 * - Use preprocessor->field() for compiling update and insert values, or aggregation projection, group, order columns
 * - For compile projection (SELECT columns), use preprocessor->root() for select all columns
 * - preprocessor->table() for register new tables / relations (FROM & JOIN)
 * - Use preprocessor->expression() for compile filter (WHERE, ON, HAVING) expression
 *
 * @template Q as \Bdf\Prime\Query\CompilableClause&\Bdf\Prime\Query\Contract\Compilable
 * @template C as ConnectionInterface
 *
 * @implements CompilerInterface<Q>
 */
abstract class AbstractCompiler implements CompilerInterface
{
    /** @use InsertCompilerTrait<Q> */
    use InsertCompilerTrait;

    /** @use UpdateCompilerTrait<Q> */
    use UpdateCompilerTrait;

    /** @use DeleteCompilerTrait<Q> */
    use DeleteCompilerTrait;

    /** @use SelectCompilerTrait<Q> */
    use SelectCompilerTrait;

    /**
     * The connection platform
     *
     * @var C
     */
    protected ConnectionInterface $connection;


    /**
     * AbstractCompiler constructor.
     *
     * @param C $connection
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function platform(): PlatformInterface
    {
        return $this->connection->platform();
    }

    /**
     * Try to resolve type and auto convert value
     *
     * @param mixed $value
     *
     * @return mixed
     * @throws PrimeException
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
     * @throws PrimeException
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
}
