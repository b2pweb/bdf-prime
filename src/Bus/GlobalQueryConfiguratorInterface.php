<?php

namespace Bdf\Prime\Bus;

use Attribute;
use Bdf\Prime\Query\ReadCommandInterface;

/**
 * Base type for attributes that are defined on the class level of the query DTO, and used to configure the query.
 * The attribute must have the target {@see Attribute::TARGET_CLASS}.
 *
 * @see PropertyQueryConfiguratorInterface To configure the query based on a property value.
 */
interface GlobalQueryConfiguratorInterface
{
    /**
     * Configure the query
     *
     * @param ReadCommandInterface $query The query to configure
     * @param object $dto The query DTO instance, where the attribute is defined
     *
     * @return ReadCommandInterface The configured query. Most of the case, the instance if same as $query parameter
     */
    public function configureQueryForDto(ReadCommandInterface $query, object $dto): ReadCommandInterface;
}
