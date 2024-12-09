<?php

namespace Bdf\Prime\Types;

use BackedEnum;

use LogicException;

use function is_subclass_of;

class BackedEnumType extends AbstractFacadeType
{
    public const INT_ENUM = 'int_enum';
    public const STRING_ENUM = 'string_enum';

    /**
     * {@inheritdoc}
     */
    protected function defaultType(): string
    {
        if ($this->type === self::INT_ENUM) {
            return self::INTEGER;
        }

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

        if ($enumClass === null || !is_subclass_of($enumClass, BackedEnum::class)) {
            throw new LogicException('The "className" option must be set and must be an enum class name.');
        }

        return $enumClass::tryFrom($value);
    }

    /**
     * {@inheritdoc}
     */
    public function toDatabase($value)
    {
        if (!$value instanceof BackedEnum) {
            return $value;
        }

        return $value->value;
    }

    /**
     * {@inheritdoc}
     */
    public function phpType(): string
    {
        return BackedEnum::class; // @todo use the real class name
    }
}
