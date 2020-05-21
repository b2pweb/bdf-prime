<?php

namespace Bdf\Prime\Migration;


interface VersionRepositoryInterface
{
    /**
     * Get a new identifier
     *
     * @return string
     */
    public function newIdentifier(): string;

    /**
     * Check if version is up
     *
     * @param string $version
     *
     * @return boolean
     */
    public function has(string $version): bool;

    /**
     * Get the current version
     *
     * @return string
     */
    public function current(): string;

    /**
     * Get all versions
     *
     * @return array
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
     */
    public function remove(string $version);
}