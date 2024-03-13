<?php

namespace Bdf\Prime\Entity\Hydrator\Generator;

use Bdf\Prime\Entity\Hydrator\Exception\HydratorGenerationException;
use Bdf\Prime\ValueObject\ValueObjectInterface;

/**
 * Accessor for embedded entity
 */
class EmbeddedAccessor
{
    private CodeGenerator $code;
    private EmbeddedInfo $embedded;

    /**
     * @var ClassAccessor[]
     */
    private array $accessors;

    /**
     * @var EmbeddedAccessor|ClassAccessor
     */
    private object $parentAccessor;


    /**
     * EmbeddedAccessor constructor.
     *
     * @param CodeGenerator $code
     * @param EmbeddedInfo $embedded
     * @param ClassAccessor[] $accessors
     * @param EmbeddedAccessor|ClassAccessor $parentAccessor
     */
    public function __construct(CodeGenerator $code, EmbeddedInfo $embedded, array $accessors, $parentAccessor)
    {
        $this->code = $code;
        $this->embedded  = $embedded;
        $this->accessors = $accessors;
        $this->parentAccessor = $parentAccessor;
    }

    /**
     * Get owner object attribute
     *
     * @todo $lastVarName
     *
     * @param string $target The target var
     * @param bool $instantiate Instantiate the last embedded object
     * @param string|null $rawDataVarName The variable name which store raw database data
     *
     * @return string
     *
     * @throws HydratorGenerationException
     */
    public function getEmbedded(string $target, bool $instantiate = true, ?string $rawDataVarName = null): string
    {
        return <<<PHP
{ //START accessor for {$this->embedded->path()}
{$this->code->indent($this->recursiveGetEmbedded('$object', $target, $instantiate, $rawDataVarName), 1)}
} //END accessor for {$this->embedded->path()}

PHP;
    }

    /**
     * Generate getter for one attribute
     * If $valueObjectClass is given this method will generate the call to {@see ValueObjectInterface::value()} if the attribute is configured as value object
     *
     * /!\ The generated code by getEmbedded() must be set before, with $varName with same value as $target
     *
     * @param string $varName The object var name
     * @param string $attribute The attribute to get
     * @param class-string<ValueObjectInterface>|null $valueObjectClass The value object class name, to unwrap the value
     *
     * @return string
     *
     * @throws HydratorGenerationException When the attribute is not readable
     */
    public function getter(string $varName, string $attribute, ?string $valueObjectClass = null): string
    {
        if (count($this->accessors) === 1) {
            $getter = $this->accessors[0]->primitiveGetter($varName, $attribute, $valueObjectClass);

            return $this->nillable()
                ? '('.$varName.' === null ? null : '.$getter.')'
                : $getter
            ;
        }

        $getter = '';

        foreach ($this->accessors as $accessor) {
            $getter .= '('.$varName.' instanceof '.$this->code->className($accessor->className()).' ? '.$accessor->primitiveGetter($varName, $attribute, $valueObjectClass).' : ';
        }

        $getter .= 'null'.str_repeat(')', count($this->accessors));

        return $getter;
    }

    /**
     * Generate setter for one attribute
     *
     * /!\ The generated code by getEmbedded() must be set before, with $varName with same value as $target
     *
     * @param string $varName The object variable name
     * @param string $attribute The attribute to set
     * @param string $value The value to pass
     * @param bool $useSetterInPriority For use setter if exists (instead of direct property set)
     *
     * @return string
     *
     * @throws HydratorGenerationException When the property is not accessible
     */
    public function setter(string $varName, string $attribute, string $value, bool $useSetterInPriority = true): string
    {
        if (count($this->accessors) === 1) {
            $setter = $this->accessors[0]->setter($varName, $attribute, $value, $useSetterInPriority);

            return $this->nillable()
                ? '('.$varName.' === null ? null : '.$setter.')'
                : $setter
            ;
        }

        $setter = '';

        foreach ($this->accessors as $accessor) {
            $setter .= '('.$varName.' instanceof '.$this->code->className($accessor->className()).' ? '.$accessor->setter($varName, $attribute, $value, $useSetterInPriority).' : ';
        }

        $setter .= 'null'.str_repeat(')', count($this->accessors));

        return $setter;
    }

    /**
     * Generate setter for one attribute, and wrap the value in case of value object
     *
     * /!\ The generated code by getEmbedded() must be set before, with $varName with same value as $target
     *
     * @param string $varName The object variable name
     * @param string $attribute The attribute to set
     * @param string $value The value to pass
     * @param class-string<ValueObjectInterface>|null $valueObjectClass The value object class name
     * @param bool $allowWrappedValue Allow the value to be wrapped in value object
     *
     * @return string
     *
     * @throws HydratorGenerationException When the property is not accessible
     */
    public function valueObjectSetter(string $varName, string $attribute, string $value, ?string $valueObjectClass = null, bool $allowWrappedValue = false): string
    {
        if ($valueObjectClass) {
            $tmp = '$__tmp'. md5($value);
            $condition = "({$tmp} = {$value}) !== null";

            if ($allowWrappedValue) {
                $condition .= " && !{$tmp} instanceof \\{$valueObjectClass}";
            }

            $value = "($condition ? \\{$valueObjectClass}::from({$tmp}) : {$tmp})";
        }

        return $this->setter($varName, $attribute, $value, true);
    }

    /**
     * Generate full setter code for an embedded attribute
     * Unlike setter(), there is no need to call getEmbedded()
     *
     * @param string $attribute The attribute to get
     * @param string $value The value to set
     * @param string $tmpVarName Temporary variable used for store the embedded instance
     * @param string|null $rawDataVarName The variable name which store raw database data
     * @param class-string<ValueObjectInterface>|null $valueObjectClass The value object class name
     * @param bool $allowWrappedValue Allow the value to be wrapped in value object
     *
     * @return string
     *
     * @throws HydratorGenerationException When the property is not accessible
     */
    public function fullSetter(string $attribute, string $value, string $tmpVarName = '$__owner', ?string $rawDataVarName = null, ?string $valueObjectClass = null, bool $allowWrappedValue = false): string
    {
        return $this->code->lines([
            $this->getEmbedded($tmpVarName, true, $rawDataVarName),
            $this->valueObjectSetter($tmpVarName, $attribute, $value, $valueObjectClass, $allowWrappedValue)
        ]);
    }

    /**
     * @param string $parent
     * @param string $target
     * @param bool $instantiate
     * @param string|null $rawDataVarName
     * @param bool $nillable
     *
     * @return string
     *
     * @throws HydratorGenerationException
     */
    private function recursiveGetEmbedded(string $parent, string $target, bool $instantiate = true, ?string $rawDataVarName = null, bool &$nillable = false): string
    {
        $accessor = '';
        $lastVar = $parent;

        if ($this->parentAccessor instanceof EmbeddedAccessor) {
            $accessor = $this->parentAccessor->recursiveGetEmbedded($parent, $lastVar = $this->code->tmpVar(), true, $rawDataVarName, $nillable).$this->code->eol().$this->code->eol();
        }

        $currentAccessor = $this->getOrInstantiate($lastVar, $target, $instantiate, $rawDataVarName);

        if ($nillable) {
            $currentAccessor = <<<PHP
if ({$lastVar} !== null) {
{$this->code->indent($currentAccessor, 1)}
}

PHP;
        }

        $accessor .= $currentAccessor;

        if ($this->embedded->isPolymorph()) {
            $nillable = true;
        }

        return $accessor;
    }

    /**
     * Get the embedded object, or instantiate it if null
     *
     * @param string $parent The parent (owner) var name
     * @param string $target The target var name
     * @param bool $instantiate Instantiate the object if null ?
     * @param string|null $rawDataVarName The database raw data var name
     *
     * @return string
     *
     * @throws HydratorGenerationException When the property is not accessible
     */
    private function getOrInstantiate(string $parent, string $target, bool $instantiate, ?string $rawDataVarName): string
    {
        $accessorsSwitch = [];

        foreach ($this->ownerAccessors() as $classAccessor) {
            $accessorCode = "{$target} = {$classAccessor->getter($parent, $this->embedded->property())};";
            $className = '\\'.ltrim($classAccessor->className(), '\\');

            if ($instantiate) {
                $accessorCode = <<<PHP
try {
    {$accessorCode}
} catch (\Error \$e) {
    // Ignore not initialized property if embedded is instantiated
    {$target} = null;
}
PHP;


                $classes = $this->embedded->classes();

                // Indexed array : no discriminator value
                if ($classes === array_values($classes)) {
                    $accessorCode .= <<<PHP

if ({$target} === null) {
    {$target} = \$this->__instantiator->instantiate('{$classes[0]}', {$this->code->export($this->embedded->hint())});
    {$classAccessor->setter($parent, $this->embedded->property(), $target, false)};
}
PHP;
                } elseif ($rawDataVarName !== null) { // Discriminator can be resolved
                    $cases = [];

                    foreach ($classes as $discriminator => $class) {
                        // If instance do not corresponds, reinstantiate the class
                        $cases[$discriminator] = <<<PHP
if (!{$target} instanceof {$this->code->className($class)}) {
    {$target} = \$this->__instantiator->instantiate('{$class}', {$this->code->export($this->embedded->hint($class))});
    {$classAccessor->setter($parent, $this->embedded->property(), $target, false)};
}
PHP;
                    }

                    // @todo Que faire en cas default ? Exception ?
                    $accessorCode .= $this->code->eol().$this->code->switch($rawDataVarName.'[\''.$this->embedded->discriminatorField().'\']', $cases);
                }
            }

            $accessorsSwitch[$className] = $accessorCode;
        }

        // Only one possible class : Do not switch instanceof
        if (count($accessorsSwitch) === 1) {
            return reset($accessorsSwitch);
        }

        return $this->code->switchIntanceOf($parent, $accessorsSwitch);
    }

    /**
     * Get list of embedded owner accessors
     *
     * @return ClassAccessor[]
     */
    private function ownerAccessors(): array
    {
        if ($this->parentAccessor instanceof EmbeddedAccessor) {
            return $this->parentAccessor->accessors;
        }

        return [$this->parentAccessor];
    }

    /**
     * Check if the current embedded can be null
     *
     * @return bool
     */
    private function nillable(): bool
    {
        if ($this->embedded->isPolymorph()) {
            return true;
        }

        if ($this->parentAccessor instanceof EmbeddedAccessor) {
            return $this->parentAccessor->nillable();
        }

        return false;
    }
}
