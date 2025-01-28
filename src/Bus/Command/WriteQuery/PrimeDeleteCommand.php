<?php

namespace Bdf\Prime\Bus\Command\WriteQuery;

use Attribute;
use Bdf\Prime\Bus\Command\CommandMetadataExtractorInterface;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * Attribute for build a delete query on a prime command
 *
 * Usage:
 * ```php
 * #[PrimeDeleteCommand(User::class)]
 * class MyCommand
 * {
 *     public function __construct(
 *         // Annotate filters using Criterion attributes
 *         #[Criterion]
 *         public readonly string $name,
 *
 *         #[Criterion(operator: '<=')]
 *         public readonly DateTimeImmutable $before,
 *     ) {}
 * }
 *
 * $bus->execute(new MyCommand('John', new DateTimeImmutable('2021-01-01')));
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class PrimeDeleteCommand implements WriteQueryInterface
{
    public function __construct(
        /**
         * The entity class name to write
         *
         * @var class-string
         */
        public readonly string $entity,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function entity(): string
    {
        return $this->entity;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(RepositoryInterface $repository, object $command, CommandMetadataExtractorInterface $extractor): void
    {
        $query = $repository->queries()->builder();
        $query->where($extractor->criteria($command));

        $query->delete();
    }
}
