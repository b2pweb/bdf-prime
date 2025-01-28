<?php

namespace Bdf\Prime\Bus\Query\Configurator;

use Bdf\Prime\Bus\Query\Execution\QueryExecutionMethodInterface;

/**
 * Base type for attributes that can be used to define execution options
 *
 * The attribute can be placed on the class, or on the property.
 * If it's placed on the property, the property value will pas passed on the method {@see ExecutionOptionInterface::executionOptions())},
 * If it's placed on the class, the value will be passed as null.
 *
 * @see QueryExecutionMethodInterface::execute()
 */
interface ExecutionOptionInterface
{
    /**
     * Extract execution options from the attribute and property value
     *
     * @param mixed|null $propertyValue The property value, or null if the attribute is placed on the class
     * @return array<string, mixed>
     */
    public function executionOptions(mixed $propertyValue = null): array;
}
