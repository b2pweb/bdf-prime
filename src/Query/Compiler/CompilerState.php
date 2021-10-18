<?php

namespace Bdf\Prime\Query\Compiler;

/**
 * Data structure used for compiling the query
 *
 * @internal
 */
class CompilerState
{
    /**
     * The bind parameters
     *
     * @var array
     */
    public $bindings = [];

    /**
     * The current part in processing for bindings
     *
     * @var string|int
     */
    public $currentPart;

    /**
     * The compiled sql parts
     *
     * @var array
     */
    public $compiledParts = [];

    /**
     * The compiled query
     *
     * @var mixed
     */
    public $compiled;

    /**
     * Does the query is compiling ?
     *
     * @var bool
     */
    public $compiling = false;

    /**
     * Invalidate compiled parts
     *
     * @param string|array $parts Parts to invalidate
     *
     * @return void
     */
    public function invalidate($parts = []): void
    {
        if ($this->compiling) {
            return;
        }

        //TODO quand doit on unset l'index '0' ?

        foreach ((array)$parts as $part) {
            unset($this->bindings[$part]);
            unset($this->compiledParts[$part]);
        }

        $this->compiled = null;
    }

    /**
     * Check if the query part needs to be compiled
     *
     * @param string $part Part to check
     *
     * @return bool
     */
    public function needsCompile(string $part): bool
    {
        return !isset($this->compiledParts[$part]);
    }

    /**
     * Bind a value to the compiled query
     * /!\ The value should be converted to database value
     *
     * @param mixed $value
     *
     * @return void
     */
    public function bind($value): void
    {
        $this->bindings[$this->currentPart][] = $value;
    }

    /**
     * Free compiled parts (like prepared statements) when state is destructed
     *
     * @see http://192.168.0.187:3000/issues/16680
     */
    public function __destruct()
    {
        $this->bindings = null;
        $this->compiledParts = null;
        $this->compiled = null;
    }
}
