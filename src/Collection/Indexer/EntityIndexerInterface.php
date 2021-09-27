<?php

namespace Bdf\Prime\Collection\Indexer;

/**
 * Indexing entities by given attribute
 * Generate indexes should be kept in memory for not regenerate the same index
 *
 * @template E as object
 */
interface EntityIndexerInterface
{
    /**
     * Indexing entities by given key
     * If a duplicate key is found, all entities will be added in a sub-array
     *
     * Note: The indexing key should be a declared attribute. Calling with undeclared attribute (or relation attribute) can result to undefined behavior (or exception)
     *
     * Ex:
     * <code>
     * $jd = new Person(['firstName' => 'John', 'lastName' => 'Doe']);
     * $js = new Person(['firstName' => 'John', 'lastName' => 'Smith']);
     * $as = new Person(['firstName' => 'Alan', 'lastName' => 'Smithy']);
     *
     * $indexer = new Indexer(Person::mapper());
     *
     * $indexer->push($jd);
     * $indexer->push($js);
     * $indexer->push($as);
     *
     * $indexer->by('firstName') === [
     *     'John' => [$jd, $js],
     *     'Alan' => [$as]
     * ];
     * </code>
     *
     * @param string $key The indexing key
     *
     * @return E[][]
     *
     * @throws \InvalidArgumentException When given key is not valid
     */
    public function by(string $key): array;

    /**
     * Indexing entities by given key
     * If a duplicate key is found, only the last entity is kept
     *
     * Note: The indexing key should be a declared attribute. Calling with undeclared attribute (or relation attribute) can result to undefined behavior (or exception)
     *
     * Ex:
     * <code>
     * $jd = new Person(['firstName' => 'John', 'lastName' => 'Doe']);
     * $js = new Person(['firstName' => 'John', 'lastName' => 'Smith']);
     * $as = new Person(['firstName' => 'Alan', 'lastName' => 'Smithy']);
     *
     * $indexer = new Indexer(Person::mapper());
     *
     * $indexer->push($jd);
     * $indexer->push($js);
     * $indexer->push($as);
     *
     * $indexer->by('firstName') === [
     *     'John' => $js,
     *     'Alan' => $as
     * ];
     * </code>
     *
     * @param string $key The indexing key
     *
     * @return E[]
     *
     * @throws \InvalidArgumentException When given key is not valid
     */
    public function byOverride(string $key): array;

    /**
     * Get all entities present in the indexer without any indexation
     *
     * @return E[]
     */
    public function all(): array;

    /**
     * Check if the indexer do not contains any entities
     *
     * @return bool
     */
    public function empty(): bool;
}
