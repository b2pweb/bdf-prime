<?php

namespace Bdf\Prime\Types\Helpers;

use Bdf\Prime\Types\PhpTypeInterface;

/**
 * JSON object type
 */
trait JsonHelper
{
    /**
     * Transform data to array
     */
    protected bool $toArray = false;

    /**
     * {@inheritdoc}
     */
    public function toDatabase($value): ?string
    {
        if ($value === null) {
            return null;
        }

        return json_encode($value);
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value, array $fieldOptions = []): mixed
    {
        if ($value === null) {
            return null;
        }

        return json_decode($value, $this->toArray);
    }

    /**
     * {@inheritdoc}
     */
    public function phpType(): string
    {
        return $this->toArray ? PhpTypeInterface::TARRAY : PhpTypeInterface::OBJECT;
    }
}
