<?php

namespace Bdf\Prime\Bus\Command\WriteQuery;

use Attribute;
use Bdf\Prime\Bus\Command\CommandMetadataExtractorInterface;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * Attribute for build an update query on a prime command
 *
 * Usage:
 * ```php
 * #[PrimeUpdateCommand(User::class)]
 * class MyCommand
 * {
 *     public function __construct(
 *         // Annotate filters using Criterion attributes to configure the WHERE clause
 *         #[Criterion]
 *         public readonly string $name,
 *
 *         #[Criterion('createdAt', operator: '<=')]
 *         public readonly DateTimeImmutable $before,
 *
 *         // Use SetValue attribute to configure the SET clause
 *         #[SetValue]
 *         public readonly bool $enabled = false,
 *
 *         // You can ignore null values by setting the "skipNull" option to true
 *         // By default null values are included in the SET clause
 *         #[SetValue(skipNull: true)]
 *         public readonly ?string $reason,
 *
 *         // Expression can be used as value, with addition of transformer if you want to transform the value to an SQL expression
 *         #[SetValue(transformer: [self::class, 'addToLogExpression'])]
 *         public readonly string $log,
 *     ) {}
 *
 *     public static function addToLogExpression(string $value): ExpressionInterface
 *     {
 *         return new Attribute('logs', '%s || "' . addslaches($value) . '"');
 *     }
 * }
 *
 * $bus->execute(new MyCommand('John', new DateTimeImmutable('2021-01-01')));
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class PrimeUpdateCommand implements WriteQueryInterface
{
    public function __construct(
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

        $query->update($extractor->values($command));
    }
}
