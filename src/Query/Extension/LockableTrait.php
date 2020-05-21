<?php

namespace Bdf\Prime\Query\Extension;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Doctrine\DBAL\LockMode;

/**
 * Trait for @see Lockable
 *
 * @property CompilerInterface $compiler
 * @property array $statements
 */
trait LockableTrait
{
    /**
     * @see Lockable::lock()
     */
    public function lock($lock = LockMode::PESSIMISTIC_WRITE)
    {
        $this->compilerState->invalidate('lock');

        $this->statements['lock'] = $lock;

        return $this;
    }

    /**
     * @see Lockable::isLocked()
     */
    public function isLocked($lock = LockMode::PESSIMISTIC_WRITE)
    {
        return $this->statements['lock'] === $lock;
    }
}
