<?php

namespace Bdf\Prime\Types;

use Bdf\Prime\Types\Helpers\JsonHelper;

/**
 * JSON object type
 */
abstract class AbstractJsonType extends AbstractFacadeType
{
    use JsonHelper;

    /**
     * {@inheritdoc}
     */
    protected function defaultType()
    {
        return self::TEXT;
    }
}
