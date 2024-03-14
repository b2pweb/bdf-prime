<?php

namespace Bdf\Prime\Clock;

use Psr\Clock\ClockInterface;

/**
 * Base type for classes that need a clock instance
 */
interface ClockAwareInterface
{
    /**
     * Set the clock instance from the service locator
     *
     * @param ClockInterface $clock
     * @return void
     */
    public function setClock(ClockInterface $clock): void;
}
