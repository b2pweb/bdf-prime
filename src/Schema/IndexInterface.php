<?php

namespace Bdf\Prime\Schema;

/**
 * Interface for represents index
 */
interface IndexInterface
{
    public const TYPE_SIMPLE  = 0;
    public const TYPE_UNIQUE  = 1;
    public const TYPE_PRIMARY = 3; // 3 = 2|1

    /**
     * Get the index name
     * It may be null : in this case, use NamedIndex
     *
     * @return string|null
     * @psalm-ignore-nullable-return
     */
    public function name(): ?string;

    /**
     * Check if the index is unique
     *
     * @return bool
     */
    public function unique(): bool;

    /**
     * Check if the index is primary
     *
     * @return bool
     */
    public function primary(): bool;

    /**
     * Get the index type
     *
     * @return IndexInterface::TYPE_*
     */
    public function type(): int;

    /**
     * Get list of composed fields
     *
     * @return string[]
     */
    public function fields(): array;

    /**
     * Check if the index is composite (i.e. have multiple fields)
     *
     * @return bool
     */
    public function isComposite(): bool;

    /**
     * Gets the index options
     *
     * @return array
     */
    public function options(): array;

    /**
     * Get options for one field
     * If the field has no options, an empty array is returned
     *
     * @param string $field The field name
     *
     * @return array
     */
    public function fieldOptions(string $field): array;
}
