<?php

namespace Bdf\Prime\Types;

use LogicException;
use UnitEnum;

use function constant;
use function defined;
use function is_subclass_of;

final class UnitEnumType extends AbstractFacadeType
{
    public const UNIT_ENUM = 'unit_enum';

    /**
     * {@inheritdoc}
     */
    protected function defaultType(): string
    {
        return self::STRING;
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value, array $fieldOptions = [])
    {
        if ($value === null) {
            return null;
        }

        $enumClass = $fieldOptions['className'] ?? null;

        if ($enumClass === null || !is_subclass_of($enumClass, UnitEnum::class)) {
            throw new LogicException('The "className" option must be set and must be an enum class name.');
        }

        $constName = $enumClass . '::' . $value;

        return defined($constName) ? constant($constName) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function toDatabase($value)
    {
        if (!$value instanceof UnitEnum) {
            return $value;
        }

        return $value->name;
    }

    /**
     * {@inheritdoc}
     */
    public function phpType(): string
    {
        return UnitEnum::class; // @todo use the real class name
    }
}
