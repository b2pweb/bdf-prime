<?php

namespace Bdf\Prime\Query\Extension;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Compiler\CompilerState;
use Bdf\Prime\Query\Compiler\DeleteCompilerInterface;
use Bdf\Prime\Query\Compiler\InsertCompilerInterface;
use Bdf\Prime\Query\Compiler\SelectCompilerInterface;
use Bdf\Prime\Query\Compiler\UpdateCompilerInterface;
use Bdf\Prime\Query\Contract\Compilable;

/**
 * Simple implementation for @see Compilable
 *
 * @property CompilerState $compilerState
 * @property object $compiler
 *
 * @psalm-require-implements Compilable
 */
trait CompilableTrait
{
    /**
     * @var Compilable::TYPE_*
     */
    protected $type = Compilable::TYPE_SELECT;

    /**
     * {@inheritdoc}
     */
    public function compile(bool $forceRecompile = false)
    {
        if ($forceRecompile) {
            $this->compilerState->invalidate('prepared');
        } elseif ($this->compilerState->compiled) {
            return $this->compilerState->compiled;
        }

        return $this->compilerState->compiled = $this->doCompilation($this->type, $this->compiler);
    }

    /**
     * {@inheritdoc}
     */
    public function getBindings(): array
    {
        return $this->compiler->getBindings($this);
    }

    /**
     * {@inheritdoc}
     *
     * @return Compilable::TYPE_*
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * Change the query type
     * This action will invalidate the current query
     *
     * @param Compilable::TYPE_* $type One of the Compilable::TYPE_* constant
     *
     * @return void
     */
    protected function setType(string $type): void
    {
        if ($this->type !== $type) {
            $this->compilerState->invalidate();
            $this->type = $type;
        }
    }

    /**
     * Perform the query compilation
     * Can be overridden for perform custom compilation process
     *
     * @param Compilable::TYPE_* $type The query type
     * @param object $compiler The related compiler
     *
     * @return mixed The compiled query
     *
     * @throws PrimeException When the compilation fail
     * @throws \LogicException If type is not supported by the query or the compiler
     */
    protected function doCompilation(string $type, object $compiler)
    {
        switch (true) {
            case $type === Compilable::TYPE_SELECT && $compiler instanceof SelectCompilerInterface:
                return $compiler->compileSelect($this);

            case $type === Compilable::TYPE_UPDATE && $compiler instanceof UpdateCompilerInterface:
                return $compiler->compileUpdate($this);

            case $type === Compilable::TYPE_INSERT && $compiler instanceof InsertCompilerInterface:
                return $compiler->compileInsert($this);

            case $type === Compilable::TYPE_DELETE && $compiler instanceof DeleteCompilerInterface:
                return $compiler->compileDelete($this);

            default:
                throw new \LogicException('The query ' . static::class . ' do not supports type ' . $type);
        }
    }
}
