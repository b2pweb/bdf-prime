<?php

namespace Bdf\Prime\Query\Criteria;

use Bdf\Prime\Query\Clause;
use ReflectionAttribute;
use ReflectionClass;
use Traversable;

/**
 * Utility class for loading criteria from attributes
 */
final class AttributeCriteriaLoader
{
    /**
     * Cache the map of criteria for each class
     * The key is the criteria class name, and the value is a map of property name to Criterion instance
     *
     * @var array<class-string, array<string, Criterion>>
     */
    private array $cache = [];

    /**
     * Convert a criteria DTO and criterion properties to an iterator compatible with {@see Clause::buildClause()}
     *
     * @param object $criteria The criteria DTO
     * @param array<string, Criterion>|null $properties The map of property name to Criterion instance. If null, will be loaded using {@see load()}
     *
     * @return Traversable<string, mixed> The iterator of field => filter
     */
    public function criteria(object $criteria, ?array $properties = null): Traversable
    {
        foreach ($properties ?? $this->load($criteria::class) as $property => $criterion) {
            $value = $criteria->$property ?? null;

            if ($value === null && $criterion->skipNull) {
                continue;
            }

            $field = $criterion->field($property);
            $value = $criterion->value($value);

            if (is_string($field)) {
                if ($criterion->operator !== null) {
                    $field .= ' ' . $criterion->operator;
                }

                yield $field => $value;
                continue;
            }

            yield $property => new FilterEntry($field, $criterion->operator ?? '=', $value);
        }
    }

    /**
     * Load criterion per property using the {@see Criterion} attribute on properties
     * The result of this method is cached.
     *
     * @param class-string $criteriaClassName The class name of the criteria
     *
     * @return array<string, Criterion> The map of property name to Criterion instance
     */
    public function load(string $criteriaClassName): array
    {
        if (($cached = $this->cache[$criteriaClassName] ?? null) !== null) {
            return $cached;
        }

        $criteria = [];

        foreach ((new ReflectionClass($criteriaClassName))->getProperties() as $property) {
            foreach ($property->getAttributes(Criterion::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                $criteria[$property->getName()] = $attribute->newInstance();
                break; // Keep only the first attribute
            }
        }

        return $this->cache[$criteriaClassName] = $criteria;
    }
}
