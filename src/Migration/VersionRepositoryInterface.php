<?php

namespace Bdf\Prime\Migration;

use Bdf\Prime\Exception\PrimeException;

interface VersionRepositoryInterface
{
    /**
     * Get a new identifier
     *
     * @return string
     * @throws PrimeException
     */
    public function newIdentifier(): string;

    /**
     * Check if version is up
     *
     * @param string $version
     *
     * @return boolean
     * @throws PrimeException
     */
    public function has(string $version): bool;

    /**
     * Get the current version
     *
     * @return string
     * @throws PrimeException
     */
    public function current(): string;

    /**
     * Get all versions
     *
     * @return array
     * @throws PrimeException
     */
    public function all(): array;

    /**
     * Add a version
     *
     * Mark this version as upgrade
     *
     * @param string $version
     *
     * @return $this
     * @throws PrimeException
     */
    public function add(string $version);

    /**
     * Remove a version
     *
     * Mark this version as downgrade
     *
     * @param string $version
     *
     * @return $this
     * @throws PrimeException
     */
    public function remove(string $version);
}
