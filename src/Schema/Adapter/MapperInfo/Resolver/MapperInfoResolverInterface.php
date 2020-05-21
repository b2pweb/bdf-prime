<?php

namespace Bdf\Prime\Schema\Adapter\MapperInfo\Resolver;

use Bdf\Prime\Mapper\Info\MapperInfo;
use Bdf\Prime\Mapper\Info\ObjectPropertyInfo;

/**
 * Extract schema information from MapperInfo
 */
interface MapperInfoResolverInterface
{
    /**
     * Extract information from relation
     *
     * @param MapperInfo $info
     * @param ObjectPropertyInfo $relation
     *
     * @return array
     */
    public function fromRelation(MapperInfo $info, ObjectPropertyInfo $relation);
}
