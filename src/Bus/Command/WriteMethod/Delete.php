<?php

namespace Bdf\Prime\Bus\Command\WriteMethod;

use Attribute;
use Bdf\Prime\Repository\RepositoryInterface;
use Bdf\Prime\Repository\Write\BufferedWriter;

use function is_iterable;

/**
 * Annotate a property that contains entities to delete
 *
 * Usage:
 * ```php
 * class MyCommand
 * {
 *     public function __construct(
 *         #[Delete]
 *         public readonly MyEntity $entity,
 *
 *         // Specify the class of the entity to perform bulk delete
 *         #[Delete(MyEntity::class)]
 *         public readonly array $entities,
 *     ) {}
 * }
 * ```
 *
 * @see RepositoryInterface::delete() The actual method called
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Delete implements WriteMethodInterface
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
            $repository->delete($entity);
            return;
        }

        /** @psalm-suppress InvalidArgument */
        $writer = (new BufferedWriter($repository));

        foreach ($entity as $e) {
            $writer->delete($e);
        }

        $writer->flush();
    }
}
