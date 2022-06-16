<?php

namespace Bdf\Prime\Entity\Hydrator\Generator;

use Bdf\Prime\Entity\Hydrator\Exception\HydratorGenerationException;
use ReflectionException;
use ReflectionProperty;

/**
 * Handle accessor from class
 */
class ClassAccessor
{
    public const SCOPE_EXTERNAL = 'external';
    public const SCOPE_INHERIT  = 'inherit';

    /**
     * The class name
     *
     * @var string
     */
    private $className;

    /**
     * @var string[]
     */
    private $subClasses;

    /**
     * The scope which accessor will be called
     *
     * @var string
     */
    private $scope;

    /**
     * @var \ReflectionClass
     */
    private $reflection;


    /**
     * ClassAccessor constructor.
     *
     * @param string $className
     * @param string $scope
     * @param array $subClasses List of potential sub-classes which the property can be redefined
     *
     * @throws ReflectionException
     */
    public function __construct($className, $scope, array $subClasses = [])
    {
        $this->className = $className;
        $this->scope = $scope;
        $this->subClasses = $subClasses;

        $this->reflection = new \ReflectionClass($className);
    }

    /**
     * Get the relected class name
     *
     * @return string
     */
    public function className()
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
    public function getter($varName, $attribute)
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
    public function setter($varName, $attribute, $value, $useSetterInPriority = true)
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
     * Check is a property is accessible from the scope without getters
     *
     * @param string $prop The property name
     *
     * @return bool
     *
     * @throws HydratorGenerationException When the property is not accessible
     */
    public function isPropertyAccessible($prop)
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
