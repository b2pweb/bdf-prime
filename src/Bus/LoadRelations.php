<?php

namespace Bdf\Prime\Bus;

use Attribute;
use Bdf\Prime\Query\ReadCommandInterface;

#[Attribute(Attribute::TARGET_CLASS|Attribute::IS_REPEATABLE)]
final class LoadRelations implements GlobalQueryConfiguratorInterface
{
    /**
     * @var string[]
     */
    public readonly array $relations;

    public function __construct(string ...$relations)
    {
        $this->relations = $relations;
    }

    /**
     * {@inheritdoc}
     */
    public function configureQueryForDto(ReadCommandInterface $query, object $dto): ReadCommandInterface
    {
        return $query->with($this->relations);
    }
}
