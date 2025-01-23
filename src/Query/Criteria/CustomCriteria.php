<?php

namespace Bdf\Prime\Query\Criteria;

use Traversable;

/**
 * Base class for define a custom criteria using attributes on properties
 *
 * Each filter should be declared using a property with the {@see Criterion} attribute.
 * Nested filters (i.e. composite filters) can also be used when the property is an instance of {@see CriteriaInterface}
 * and with the {@see Criterion} attribute. In this case, parameters of the attribute are ignored.
 *
 * Note: all properties that are used as filters must be public
 *
 * Usage:
 *
 * ```php
 * class MyCriteria extends CustomCriteria
 * {
 *     // Simple filter: will use the property name as field, and compare the value with the operator "="
 *     #[Criterion]
 *     public string $login;
 *
 *     // You can also specify the field name and the operator
 *     #[Criterion(field: 'updatedAt', operator: '>=')]
 *     public DateTime $after;
 *
 *     // By default, null values are skipped. You can disable this behavior by setting the "skipNull" parameter to false
 *     // In this case, IS NULL will be used for the filter when the value is null
 *     #[Criterion(skipNull: false)]
 *     public ?int $value;
 *
 *     // Subclass of Criterion can be used to define more complex filters
 *     // Here a LIKE xxx% filter will be used
 *     #[StartsWithCriterion]
 *     public ?string $name;
 *
 *     // Expression can also be used instead of field name
 *     #[Criterion(field: new JsonExtract('metadata', 'tag'))]
 *     public ?int $tag;
 *
 *     // Nested filter can also be used
 *     #[Criterion]
 *     public AddressCriteria $address;
 * }
 *
 * // You can directly use the criterion on the where() method of the query
 * $criteria = new MyCriteria();
 * $entities = MyEntity::repository()->where($criteria)->all();
 * ```
 */
abstract class CustomCriteria implements CriteriaInterface
{
    /**
     * Define the separator operator to use between each criterion
     * To override this value on the subclass, set it to "AND" or "OR" if needed.
     *
     * @var string|null
     */
    protected const /*?string*/ SEPARATOR = null;

    private static ?AttributeCriteriaLoader $loader = null;

    /**
     * {@inheritdoc}
     */
    public function getIterator(): Traversable
    {
        return $this->loader()->criteria($this, $this->loadCriteria());
    }

    /**
     * {@inheritdoc}
     */
    public function separator(): ?string
    {
        return static::SEPARATOR;
    }

    /**
     * Load criterion per property
     * This method can be overridden. By default, it will load using the {@see Criterion} attribute on properties
     *
     * @return array<string, Criterion>
     */
    protected function loadCriteria(): array
    {
        return $this->loader()->load(static::class);
    }

    private function loader(): AttributeCriteriaLoader
    {
        return self::$loader ??= new AttributeCriteriaLoader();
    }
}
