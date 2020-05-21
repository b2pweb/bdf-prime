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
 */
trait CompilableTrait
{
    /**
     * @var string
     */
    protected $type = Compilable::TYPE_SELECT;

    /**
     * {@inheritdoc}
     */
    public function compile($forceRecompile = false)
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
    public function getBindings()
    {
        return $this->compiler->getBindings($this);
    }

    /**
     * {@inheritdoc}
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Change the query type
     * This action will invalidate the current query
     *
     * @param string $type One of the Compilable::TYPE_* constant
     *
     * @return void
     */
    protected function setType($type)
    {
        if ($this->type !== $type) {
            $this->compilerState->invalidate();
            $this->type = $type;
        }
    }
}
