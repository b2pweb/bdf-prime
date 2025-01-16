<?php

namespace Bdf\Prime\Bus;

use Attribute;
use Bdf\Prime\Query\ReadCommandInterface;

/**
 * @todo doc
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Projection implements GlobalQueryConfiguratorInterface
{
    public function __construct(
        public array|string $columns,
    ) {}

    /**
     * {@inheritdoc}
     */
    public function configureQueryForDto(ReadCommandInterface $query, object $dto): ReadCommandInterface
    {
        return $query->project($this->columns);
    }
}
