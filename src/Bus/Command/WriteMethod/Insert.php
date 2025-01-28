<?php

namespace Bdf\Prime\Bus\Command\WriteMethod;

use Attribute;
use Bdf\Prime\Repository\RepositoryInterface;

use function is_iterable;

/**
 * Annotate a property that contains entities to insert
 *
 * Usage:
 * ```php
 * class MyCommand
 * {
 *     public function __construct(
 *         #[Insert]
 *         public readonly MyEntity $entity,
 *
 *         // Specify the class of the entity to perform bulk insert
 *         #[Insert(MyEntity::class)]
 *         public readonly array $entities,
 *     ) {}
 * }
 * ```
 *
 * @see RepositoryInterface::insert() The actual method called
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Insert implements WriteMethodInterface
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

        /**
         * If true, an insert ignore will be performed, so the insert will not fail if the entity already exists.
         */
        public readonly bool $ignore = false,
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
            $repository->insert($entity, $this->ignore);
            return;
        }

        // Bulk insert is not optimised, so simply perform insert on each entity
        foreach ($entity as $item) {
            $repository->insert($item, $this->ignore);
        }
    }
}
