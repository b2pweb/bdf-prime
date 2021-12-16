<?php

namespace Bdf\Prime\Query\Extension;

use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Compiler\CompilerState;
use Bdf\Prime\Query\Contract\Compilable;

/**
 * Simple implementation for @see Compilable
 *
 * @property CompilerState $compilerState
 * @property CompilerInterface $compiler
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

        return $this->compilerState->compiled = $this->compiler->{'compile'.$this->type}($this);
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
}
