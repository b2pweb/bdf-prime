<?php

namespace Bdf\Prime\Schema\Adapter\MapperInfo\Resolver;

use Bdf\Prime\Mapper\Info\MapperInfo;
use Bdf\Prime\Mapper\Info\ObjectPropertyInfo;
use Bdf\Prime\Schema\Constraint\ForeignKey;
use Bdf\Prime\ServiceLocator;

/**
 * Extract foreign keys from mapper info
 */
final class MapperInfoForeignKeyResolver implements MapperInfoResolverInterface
{
    /**
     * @var ServiceLocator
     */
    private $service;


    /**
     * MapperInfoForeignKeyResolver constructor.
     *
     * @param ServiceLocator $service
     */
    public function __construct(ServiceLocator $service)
    {
        $this->service = $service;
    }

    /**
     * {@inheritdoc}
     */
    public function fromRelation(MapperInfo $info, ObjectPropertyInfo $relation)
    {
        list($entity, $foreignKey) = $relation->foreignInfos();

        if ($entity === null) {
            return [];
        }

        $property = $info->metadata()->attributes[$relation->relationKey()]['field'];
        $metadata = $this->service->repository($entity)->metadata();

        return [
            new ForeignKey(
                [$property],
                $metadata->table,
                [$metadata->attributes[$foreignKey]['field']],
                $relation->name()
            )
        ];
    }
}
