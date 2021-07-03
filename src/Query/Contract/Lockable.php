<?php

namespace Bdf\Prime\Query\Contract;

use Doctrine\DBAL\LockMode;

/**
 * Interface for lockable queries
 */
interface Lockable
{
    /**
     * Lock the row for select
     *
     * @param LockMode::* $lock  {@see LockMode} constants
     *
     * @return $this This Query instance.
     */
    public function lock(int $lock = LockMode::PESSIMISTIC_WRITE);

    /**
     * Checks whether the query is locked for select
     *
     * @param LockMode::* $lock
     *
     * @return boolean
     */
    public function isLocked(int $lock = LockMode::PESSIMISTIC_WRITE): bool;
}
