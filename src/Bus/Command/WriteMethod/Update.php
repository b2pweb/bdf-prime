<?php

namespace Bdf\Prime\Bus\Command\WriteMethod;

use Attribute;
use Bdf\Prime\Repository\RepositoryInterface;

use function is_iterable;

/**
 * Annotate a property that contains entities to update
 *
 * Usage:
 * ```php
 * class MyCommand
 * {
 *     public function __construct(
 *         #[Update]
 *         public readonly MyEntity $entity,
 *
 *         // Specify the attributes to update (by default all attributes are updated)
 *         #[Update(['name', 'email'])]
 *         public readonly MyEntity $partialUpdate,
 *
 *         // Specify the class of the entity to perform bulk update
 *         #[Update(entityClassName: MyEntity::class)]
 *         public readonly array $entities,
 *     ) {}
 * }
 * ```
 *
 * @see RepositoryInterface::update() The actual method called
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Update implements WriteMethodInterface
{
    public function __construct(
        /**
         * List of attributes to update
         * If not set, all attributes will be updated
         *
         * @var string[]|null
         */
        public readonly ?array $attributes = null,

        /**
         * The entity class name to write
         *
         * If not provided, will be resolved from the property value.
         * If the property value is a collection, the class name must be provided.
         *
         * @var class-string|null
         */
        public readonly ?string $entityClassName = null,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function entityClassName(): ?string
    {
        return $this->entityClassName;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(RepositoryInterface $repository, object|iterable $entity): void
    {
        if (!is_iterable($entity)) {
            $repository->update($entity, $this->attributes);
            return;
        }

        foreach ($entity as $item) {
            $repository->update($item, $this->attributes);
        }
    }
}
