<?php

namespace Bdf\Prime\Types;

/**
 * JSON object type
 */
class JsonType extends AbstractJsonType
{
    /**
     * {@inheritdoc}
     */
    public function __construct($type = self::JSON)
    {
        parent::__construct($type);

        $this->toArray = true;
    }
}
