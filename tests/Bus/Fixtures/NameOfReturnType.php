<?php

namespace Bus\Fixtures;

use Bdf\Prime\Bus\ReturnTypeInterface;

/**
 * @implements ReturnTypeInterface<string>
 */
class NameOfReturnType implements ReturnTypeInterface
{
    public function __construct(
        private readonly string $entity,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function unwrappedType(): ?string
    {
        return $this->entity;
    }

    /**
     * @inheritDoc
     */
    public function cast(mixed $value): ?string
    {
        return $value?->name ?? null;
    }
}
