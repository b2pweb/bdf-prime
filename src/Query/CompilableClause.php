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
    public function __construct(PreprocessorInterface $preprocessor, CompilerState $state = null)
    {
        $this->preprocessor = $preprocessor;
        $this->compilerState = $state ?: new CompilerState();
    }

    /**
     * Get the preprocessor of the query
     *
     * @return PreprocessorInterface
     */
    public function preprocessor()
    {
        return $this->preprocessor;
    }

    /**
     * Get the query compiler state
     *
     * @return CompilerState
     *
     * @internal
     */
    public function state()
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
     * Check if the identifiers should be quoted
     *
     * @return bool
     */
    public function isQuoteIdentifier()
    {
        return $this->quoteIdentifier;
    }
}
