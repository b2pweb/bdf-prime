<?php

namespace Bdf\Prime\Query\Extension;

use Bdf\Prime\Query\Compiler\CompilerState;
use Doctrine\DBAL\LockMode;

/**
 * Trait for @see Lockable
 *
 * @property CompilerState $compilerState
 * @property array $statements
 *
 * @psalm-require-implements \Bdf\Prime\Query\Contract\Lockable
 */
trait LockableTrait
{
    /**
     * {@inheritdoc}
     *
     * @see Lockable::lock()
     */
    public function lock(LockMode $lock = LockMode::PESSIMISTIC_WRITE)
    {
        $this->compilerState->invalidate('lock');

        $this->statements['lock'] = $lock;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see Lockable::isLocked()
     */
    public function isLocked(LockMode $lock = LockMode::PESSIMISTIC_WRITE): bool
    {
        return $this->statements['lock'] === $lock;
    }
}
