<?php

namespace Bdf\Prime\Bus\Command\WriteMethod;

use Bdf\Prime\Repository\RepositoryInterface;

/**
 * Base type for attributes used to perform write operations on entities.
 * Those attributes must have the attribute `#[Attribute(Attribute::TARGET_PROPERTY)]`.
 * Adding multiple write methods on the same property is undefined behavior.
 */
interface WriteMethodInterface
{
    /**
     * Get the entity class name which should be written
     *
     * If the value is null, the class name will be resolved from the property value.
     * The resolution can only be done if the property store only one entity, for a collection of entities, the class name must be provided.
     *
     * @return class-string|null
     */
    public function entityClassName(): ?string;

    /**
     * Execute the write operation
     *
     * @param RepositoryInterface $repository The repository to write on. Resolved using {@see WriteMethodInterface::entityClassName()} or the property value class.
     * @param object|iterable<object> $entity The entity, or collection of entities to write. Will be the value of the property.
     */
    public function execute(RepositoryInterface $repository, object|iterable $entity): void;
}
