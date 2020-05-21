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
     * @param int $lock  {@see LockMode} constants
     *
     * @return $this This Query instance.
     */
    public function lock($lock = LockMode::PESSIMISTIC_WRITE);

    /**
     * Checks whether the query is locked for select
     *
     * @param int $lock
     *
     * @return boolean
     */
    public function isLocked($lock = LockMode::PESSIMISTIC_WRITE);
}
