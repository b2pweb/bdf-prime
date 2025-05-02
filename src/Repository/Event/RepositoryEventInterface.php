<?php

namespace Bdf\Prime\Repository\Event;

/**
 * Events triggered by repository
 */
interface RepositoryEventInterface
{
    /**
     * Get the list or argument for legacy listeners
     *
     * @return list<mixed>
     */
    public function legacyArgs(): array;
}
