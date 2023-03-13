<?php

namespace Bdf\Prime\Query\Closure\Value;

use ReflectionFunction;

/**
 * Apply transformation on value for LIKE comparison
 */
final class LikeValue implements ComparisonValueInterface
{
    private ComparisonValueInterface $value;
    private string $prefix;
    private string $suffix;

    /**
     * @param ComparisonValueInterface $value Base value accessor
     * @param string $prefix Prefix to prepend
     * @param string $suffix Suffix to append
     */
    public function __construct(ComparisonValueInterface $value, string $prefix, string $suffix)
    {
        $this->value = $value;
        $this->prefix = $prefix;
        $this->suffix = $suffix;
    }

    /**
     * {@inheritdoc}
     */
    public function get(ReflectionFunction $reflection)
    {
        return $this->prefix . addcslashes($this->value->get($reflection), '%_') . $this->suffix;
    }

    public static function create(ComparisonValueInterface $value, string $prefix, string $suffix): ComparisonValueInterface
    {
        if ($value instanceof ConstantValue) {
            return new ConstantValue($prefix . addcslashes($value->value(), '%_') . $suffix);
        }

        return new self($value, $prefix, $suffix);
    }

    public static function startsWith(ComparisonValueInterface $value): ComparisonValueInterface
    {
        return self::create($value, '', '%');
    }

    public static function endsWith(ComparisonValueInterface $value): ComparisonValueInterface
    {
        return self::create($value, '%', '');
    }

    public static function contains(ComparisonValueInterface $value): ComparisonValueInterface
    {
        return self::create($value, '%', '%');
    }
}
