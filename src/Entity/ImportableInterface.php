<?php

namespace Bdf\Prime\Entity;

use Bdf\Prime\Entity\Hydrator\HydratorInterface;

/**
 * Interface for entities which is importable
 */
interface ImportableInterface
{
    /**
     * Hydrate the current entity
     * @see HydratorInterface::hydrate()
     *
     * @param array $data
     *
     * @return void
     */
    public function import(array $data): void;

    /**
     * Export attributes to an array
     * @see HydratorInterface::extract()
     *
     * @param list<string> $attributes
     *
     * @return array
     */
    public function export(array $attributes = []): array;
}
