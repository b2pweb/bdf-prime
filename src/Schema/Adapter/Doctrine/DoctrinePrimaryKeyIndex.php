<?php

namespace Bdf\Prime\Schema\Adapter\Doctrine;

use Bdf\Prime\Schema\IndexInterface;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Override;

use function array_map;
use function count;

/**
 * Adapt a {@see PrimaryKeyConstraint} to prime index system
 */
final readonly class DoctrinePrimaryKeyIndex implements IndexInterface
{
    public function __construct(
        private PrimaryKeyConstraint $index,
    ) {}

    #[Override]
    public function name(): ?string
    {
        // Use default name 'primary' for compatibility with previous doctrine system,
        // which set the name of the primary index to primary by default
        return $this->index->getObjectName()?->toString() ?? 'primary';
    }

    #[Override]
    public function unique(): bool
    {
        return true;
    }

    #[Override]
    public function primary(): bool
    {
        return true;
    }

    #[Override]
    public function type(): int
    {
        return self::TYPE_PRIMARY;
    }

    #[Override]
    public function fields(): array
    {
        return array_map(static fn (UnqualifiedName $name) => $name->toString(), $this->index->getColumnNames());
    }

    #[Override]
    public function isComposite(): bool
    {
        return count($this->index->getColumnNames()) > 1;
    }

    #[Override]
    public function options(): array
    {
        if (!$this->index->isClustered()) {
            return ['nonclustered' => true];
        }

        return [];
    }

    #[Override]
    public function fieldOptions(string $field): array
    {
        // Primary key cannot have a length, so fields cannot have any options
        return [];
    }
}
