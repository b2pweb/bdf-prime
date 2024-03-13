<?php

namespace Bdf\Prime\Entity\Hydrator\Generator;

use Bdf\Prime\Entity\Hydrator\Exception\HydratorGenerationException;
use Bdf\Prime\ValueObject\ValueObjectInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

use function md5;

/**
 * Handle accessor from class
 *
 * @template T as object
 */
class ClassAccessor
{
    public const SCOPE_EXTERNAL = 'external';
    public const SCOPE_INHERIT  = 'inherit';

    /**
     * The class name
     *
     * @var class-string<T>
     */
    private string $className;

    /**
     * @var string[]
     */
    private array $subClasses;

    /**
     * The scope which accessor will be called
     *
     * @var string
     */
    private string $scope;

    /**
     * @var ReflectionClass<T>
     */
    private ReflectionClass $reflection;


    /**
     * ClassAccessor constructor.
     *
     * @param class-string<T> $className
     * @param string $scope
     * @param string[] $subClasses List of potential sub-classes which the property can be redefined
     *
     * @throws ReflectionException
     */
    public function __construct(string $className, string $scope, array $subClasses = [])
    {
        $this->className = $className;
        $this->scope = $scope;
        $this->subClasses = $subClasses;

        $this->reflection = new ReflectionClass($className);
    }

    /**
     * Get the selected class name
     *
     * @return class-string<T>
     */
    public function className(): string
    {
        return $this->className;
    }

    /**
     * Generate getter for one attribute
     *
     * @param string $varName The object var name
     * @param string $attribute The attribute to get
     *
     * @return string
     *
     * @throws HydratorGenerationException When the attribute is not readable
     */
    public function getter(string $varName, string $attribute): string
    {
        if ($this->isPropertyAccessible($attribute)) {
            return $varName.'->'.$attribute;
        }

        foreach ([$attribute, 'get'.ucfirst($attribute)] as $method) {
            if (method_exists($this->className, $method)) {
                return $varName.'->'.$method.'()';
            }
        }

        throw new HydratorGenerationException($this->className, 'Cannot get the value of property "' . $attribute . '"');
    }

    /**
     * Generate the getter for one attribute, and extract the primitive value in case of value object
     * So this method will generate the call to {@see ValueObjectInterface::value()} if the attribute is configured as value object
     *
     * @param string $varName The object (entity container) var name
     * @param string $attribute The attribute to get
     * @param class-string<ValueObjectInterface>|null $valueObjectClass The value object class name
     *
     * @return string
     *
     * @throws HydratorGenerationException When the attribute is not readable
     */
    public function primitiveGetter(string $varName, string $attribute, ?string $valueObjectClass): string
    {
        $getter = $this->getter($varName, $attribute);

        if (!$valueObjectClass) {
            return $getter;
        }

        $tmp = '$__tmp'. md5($getter);

        return "(({$tmp} = {$getter}) instanceof \\{$valueObjectClass} ? {$tmp}->value() : {$tmp})";
    }

    /**
     * Generate setter for one attribute
     *
     * @param string $varName The object variable name
     * @param string $attribute The attribute to set
     * @param string $value The value to pass
     * @param bool $useSetterInPriority For use setter if exists (instead of direct property set)
     *
     * @return string
     *
     * @throws HydratorGenerationException When the attribute is not accessible
     */
    public function setter(string $varName, string $attribute, string $value, bool $useSetterInPriority = true): string
    {
        if ($useSetterInPriority && method_exists($this->className, 'set'.ucfirst($attribute))) {
            return $varName.'->set'.ucfirst($attribute).'('.$value.')';
        }

        if ($this->isPropertyAccessible($attribute)) {
            return $varName.'->'.$attribute.' = '.$value;
        }

        if (!$useSetterInPriority && method_exists($this->className, 'set'.ucfirst($attribute))) {
            return $varName.'->set'.ucfirst($attribute).'('.$value.')';
        }

        throw new HydratorGenerationException($this->className, 'Cannot access to attribute "' . $attribute . '" on write');
    }

    /**
     * Generate setter for one attribute, and wrap the value in a value object if needed
     *
     * @param string $varName The object variable name
     * @param string $attribute The attribute to set
     * @param string $value The value to pass
     * @param class-string<ValueObjectInterface>|null $valueObjectClass The value object class name. If null, the value will not be wrapped
     * @param bool $allowWrappedValue Allow to pass a value object instance as value (if true, and if the value is a value object, the value will be passed as is)
     *
     * @return string
     *
     * @throws HydratorGenerationException When the attribute is not accessible
     */
    public function valueObjectSetter(string $varName, string $attribute, string $value, ?string $valueObjectClass = null, bool $allowWrappedValue = false): string
    {
        if ($valueObjectClass) {
            $tmp = '$__tmp'. md5($value);
            $condition = "({$tmp} = {$value}) !== null";

            if ($allowWrappedValue) {
                $condition .= " && !{$tmp} instanceof \\{$valueObjectClass}";
            }

            $value = "({$condition} ? \\{$valueObjectClass}::from({$tmp}) : $tmp)";
        }

        return $this->setter($varName, $attribute, $value, false);
    }

    /**
     * Check is a property is accessible from the scope without getters
     *
     * @param string $prop The property name
     *
     * @return bool
     *
     * @throws HydratorGenerationException When the property is not accessible
     */
    public function isPropertyAccessible(string $prop): bool
    {
        try {
            $propertyReflection = $this->reflection->getProperty($prop);

            if ($propertyReflection->isPrivate()) {
                return false;
            }

            if ($propertyReflection->isPublic()) {
                return true;
            }

            if ($this->scope !== self::SCOPE_INHERIT) {
                return false;
            }

            // Protected property, without potential redefinition
            if (empty($this->subClasses)) {
                return true;
            }

            // The entity can be overloaded
            // If a protected property is redefined in a sub-class, it will not be accessible anymore in the hydrator
            // So we need to check if the property is redefined in sub-entities
            foreach ($this->subClasses as $subEntity) {
                $subEntityProperty = new ReflectionProperty($subEntity, $prop);

                // Not same class => property redefined
                if ($subEntityProperty->class !== $this->reflection->name) {
                    return false;
                }
            }
        } catch (ReflectionException $e) {
            throw new HydratorGenerationException($this->className, 'Cannot access to the property ' . $prop, $e);
        }

        return true;
    }
}
