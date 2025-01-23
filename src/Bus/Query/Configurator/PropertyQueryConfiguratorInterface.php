<?php

namespace Bdf\Prime\Bus\Query\Configurator;

use Bdf\Prime\Query\ReadCommandInterface;

/**
 * Base type for attributes that are defined on the property level of the query DTO, and used to configure the query.
 * The attribute must have the target {@see Attribute::TARGET_PROPERTY}.
 *
 * @see GlobalQueryConfiguratorInterface To configure the query based on the query DTO instance.
 */
interface PropertyQueryConfiguratorInterface
{
    /**
     * Configure the query based on the property value
     *
     * @param ReadCommandInterface $query The query to configure
     * @param mixed $propertyValue The property value
     *
     * @return ReadCommandInterface The configured query. Most of the case, the instance if same as $query parameter
     */
    public function configureQueryForProperty(ReadCommandInterface $query, mixed $propertyValue): ReadCommandInterface;
}
