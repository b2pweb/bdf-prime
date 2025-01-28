<?php

namespace Bdf\Prime\Bus\Command\WriteMethod;

use Attribute;
use Bdf\Prime\Repository\RepositoryInterface;

use function is_iterable;

/**
 * Annotate a property that contains entities to save
 *
 * Usage:
 * ```php
 * class MyCommand
 * {
 *     public function __construct(
 *         #[Save]
 *         public readonly MyEntity $entity,
 *
 *         // Specify the class of the entity to perform bulk save
 *         #[Save(MyEntity::class)]
 *         public readonly array $entities,
 *     ) {}
 * }
 * ```
 *
 * @see RepositoryInterface::save() The actual method called
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Save implements WriteMethodInterface
{
    public function __construct(
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
            $repository->save($entity);
            return;
        }

        foreach ($entity as $e) {
            $repository->save($e);
        }
    }
}
