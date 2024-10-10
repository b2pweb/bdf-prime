<?php

namespace Bdf\Prime\Query;

use Bdf\Prime\Query\Compiler\CompilerState;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;

/**
 * Base class for compilable queries
 */
class CompilableClause extends Clause implements CompilableClauseInterface
{
    /**
     * @var PreprocessorInterface
     */
    private $preprocessor;

    /**
     * @var bool
     */
    private $quoteIdentifier = false;

    private ?bool $allowUnknownAttributes = null;

    /**
     * @var CompilerState
     */
    protected $compilerState;


    /**
     * CompilableClause constructor.
     *
     * @param PreprocessorInterface $preprocessor
     * @param CompilerState|null $state
     */
    public function __construct(PreprocessorInterface $preprocessor, ?CompilerState $state = null)
    {
        $this->preprocessor = $preprocessor;
        $this->compilerState = $state ?: new CompilerState();
    }

    /**
     * {@inheritdoc}
     */
    public function preprocessor(): PreprocessorInterface
    {
        return $this->preprocessor;
    }

    /**
     * {@inheritdoc}
     */
    public function state(): CompilerState
    {
        return $this->compilerState;
    }

    /**
     * {@inheritdoc}
     */
    public function useQuoteIdentifier(bool $flag = true): void
    {
        $this->quoteIdentifier = $flag;
    }

    /**
     * {@inheritdoc}
     */
    public function isQuoteIdentifier(): bool
    {
        return $this->quoteIdentifier;
    }

    /**
     * {@inheritdoc}
     */
    public function isAllowUnknownAttribute(): ?bool
    {
        return $this->allowUnknownAttributes;
    }

    /**
     * {@inheritdoc}
     */
    public function allowUnknownAttribute(?bool $allowUnknownAttributes = true): void
    {
        $this->allowUnknownAttributes = $allowUnknownAttributes;
    }
}
