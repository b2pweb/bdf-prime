<?php

namespace Bdf\Prime\Entity\Hydrator;

use Bdf\Prime\Entity\Hydrator\Generator\AccessorResolver;
use Bdf\Prime\Entity\Hydrator\Generator\AttributeInfo;
use Bdf\Prime\Entity\Hydrator\Generator\AttributesResolver;
use Bdf\Prime\Entity\Hydrator\Generator\ClassAccessor;
use Bdf\Prime\Entity\Hydrator\Generator\CodeGenerator;
use Bdf\Prime\Entity\Hydrator\Generator\EmbeddedInfo;
use Bdf\Prime\Entity\Hydrator\Generator\TypeAccessor;
use Bdf\Prime\Exception\HydratorException;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Mapper\SingleTableInheritanceMapper;
use Bdf\Prime\ServiceLocator;

/**
 * Generator for hydrator classes
 */
class HydratorGenerator
{
    /**
     * The stub hydrator file name
     *
     * @var string
     */
    private $stub = __DIR__.'/Generator/stubs/hydrator.php.stub';

    /**
     * @var CodeGenerator
     */
    private $code;

    /**
     * @var ClassAccessor
     */
    private $accessor;

    /**
     * @var AccessorResolver
     */
    private $accessors;

    /**
     * @var AttributesResolver
     */
    private $resolver;

    /**
     * @var ServiceLocator
     */
    private $prime;

    /**
     * @var Mapper
     */
    private $mapper;

    /**
     * @var string
     */
    private $className;

    /**
     * @var string
     */
    private $interface = HydratorGeneratedInterface::class;

    /**
     * @var array
     */
    private $embeddedHydrators = [];


    /**
     * HydratorGenerator constructor.
     *
     * @param ServiceLocator $prime
     * @param Mapper $mapper
     * @param string $className
     *
     * @throws HydratorException
     */
    public function __construct(ServiceLocator $prime, Mapper $mapper, $className)
    {
        $this->prime = $prime;
        $this->mapper = $mapper;
        $this->className = $className;

        $this->code = new CodeGenerator();
        $this->accessor = $this->makeAccessor();
        $this->resolver = new AttributesResolver($mapper, $prime);
        $this->accessors = new AccessorResolver($this->accessor, $this->resolver, $this->code);
    }

    private function makeAccessor()
    {
        $subClass = [];

        // The mapper has inheritance, and it's not the inherited one
        if (
            $this->mapper instanceof SingleTableInheritanceMapper
            && !in_array(get_class($this->mapper), $this->mapper->getDiscriminatorMap())
        ) {
            $subClass = $this->mapper->getEntityMap();
        }

        return new ClassAccessor($this->className, ClassAccessor::SCOPE_INHERIT, $subClass);
    }

    /**
     * Get the hydrator namespace
     *
     * @return string
     */
    public function hydratorNamespace()
    {
        return implode('\\', array_slice(explode('\\', $this->className), 0, -1));
    }

    /**
     * Get the hydrator class name, without namespace
     *
     * @return string
     */
    public function hydratorClassName()
    {
        return 'Hydrator_' . str_replace('\\', '_', $this->className);
    }

    /**
     * Get the full class name (namespace + class name)
     *
     * @return string
     */
    public function hydratorFullClassName()
    {
        return $this->hydratorNamespace() . '\\' . $this->hydratorClassName();
    }

    /**
     * Generate the hydrator class code
     *
     * @return string
     */
    public function generate()
    {
        $this->resolveHydrators();

        return $this->hydratorTemplate();
    }

    /**
     * Resolve hydrator properties
     */
    protected function resolveHydrators()
    {
        $classes = [];

        foreach ($this->resolver->rootEmbeddeds() as $embedded) {
            foreach ($embedded->classes() as $class) {
                // For now (1.6) hydrators are used only for hydrate / extract. Remove the isImportable if hydrator are use in mapping context.
                if ($class === $this->className || !$this->resolver->isEntity($class) || $this->resolver->isImportable($class)) {
                    continue;
                }

                if (!isset($classes[$class])) {
                    $classes[$class] = true;

                    $property = '__' . str_replace('\\', '_', $class) . '_hydrator';
                    $this->embeddedHydrators[$class] = $property;
                }
            }
        }
    }

    /**
     * Get the hydrator class template
     *
     * @return string
     */
    protected function hydratorTemplate()
    {
        return $this->code->generate($this->stub, [
            'namespace'                 => $this->code->namespace($this->hydratorNamespace()),
            'normalizedEntityClassName' => $this->normalizeClassName($this->className),
            'hydratorClassName'         => $this->hydratorClassName(),
            'hydratorInterface'         => $this->normalizeClassName($this->interface),
            'properties'                => $this->code->properties($this->embeddedHydrators),
            'constructor'               => $this->code->simpleConstructor($this->embeddedHydrators),
            'hydrateBody'               => $this->generateHydrateBody(),
            'extractBody'               => $this->generateExtractBody(),
            'flatExtractBody'           => $this->generateFlatExtract(),
            'flatHydrateBody'           => $this->generateFlatHydrate(),
            'extractOneBody'            => $this->generateExtractOneBody(),
            'hydrateOneBody'            => $this->generateHydrateOneBody(),
            'entityClassName'           => $this->className,
            'embeddedClasses'           => $this->generateEmbeddedClasses(),
        ]);
    }

    /**
     * Generate the hydrate() method body
     *
     * @return string
     */
    protected function generateHydrateBody()
    {
        $out = '';

        foreach ($this->resolver->rootAttributes() as $attribute) {
            $out .= <<<PHP
if (isset(\$data['{$attribute->name()}'])) {
{$this->code->indent($this->generateAttributeHydrate($attribute), 1)}
}


PHP;
        }

        return $out;
    }

    /**
     * Generate the hydration code for one attribute
     *
     * @param AttributeInfo $attribute
     *
     * @return string
     */
    protected function generateAttributeHydrate(AttributeInfo $attribute)
    {
        $out = '';

        $value = '$data[\''.$attribute->name().'\']';

        if ($attribute->isEmbedded()) {
            $out .= <<<PHP
if (is_array({$value})) {
{$this->code->indent($this->generateEmbeddedHydrate($attribute), 1)}
} else {
    {$this->accessor->setter('$object', $attribute->property(), $value)};
}
PHP;
        } else {
            $out .= $this->accessor->setter('$object', $attribute->property(), $value).';';
        }

        return $out;
    }

    /**
     * Generate embedded hydrator
     *
     * @param AttributeInfo $attribute
     *
     * @return string
     */
    protected function generateEmbeddedHydrate(AttributeInfo $attribute)
    {
        // We can have multiple entity classes for one attribute : morph
        $varName = '$__rel_' . str_replace('.', '_', $attribute->name());

        $hydrators = [];

        foreach ($attribute->embedded()->classes() as $class) {
            if ($this->resolver->isImportable($class)) {
                // For other objects (Collections) use import() method
                $hydrators[$this->normalizeClassName($class)] = "{$varName}->import(\$data['{$attribute->name()}']);";
            } elseif ($this->resolver->isEntity($class)) {
                // For Entities, use hydrators
                $hydrators[$this->normalizeClassName($class)] = "{$this->generateEmbeddedHydrator($class)}->hydrate({$varName}, \$data['{$attribute->name()}']);";
            } else {
                throw new HydratorException($class, 'Cannot generate embedded hydration for the property "'.$attribute->name().'"');
            }
        }

        return <<<PHP
{$varName} = {$this->accessor->getter('$object', $attribute->property())};

{$this->code->switchIntanceOf($varName, $hydrators)}
PHP;

    }

    /**
     * @param string $class
     *
     * @return string
     */
    protected function generateEmbeddedHydrator($class)
    {
        if ($class === $this->className) {
            return '$this';
        }

        return '$this->' . $this->embeddedHydrators[$class];
    }

    /**
     * Add the root namespace
     *
     * @param string $className
     *
     * @return string
     */
    protected function normalizeClassName($className)
    {
        return '\\' . ltrim($className, '\\');
    }

    /**
     * Generate the embedded entities classes list
     *
     * @return string
     */
    protected function generateEmbeddedClasses()
    {
        return $this->code->export(array_keys($this->embeddedHydrators));
    }

    /**
     * Generate the {@link HydratorInterface::extract()} method's body
     *
     * @return string
     */
    protected function generateExtractBody()
    {
        return <<<PHP
if (empty(\$attributes)) {
{$this->code->indent($this->generateExtractAll(), 1)}
} else {
{$this->code->indent($this->generateExtractSelected(), 1)}
}
PHP;

    }

    /**
     * Generate extract method's code for extract all attributes
     *
     * @return string
     */
    protected function generateExtractAll()
    {
        $lines = [];

        foreach ($this->resolver->rootAttributes() as $attribute) {
            $lines[] = "'{$attribute->name()}' => ({$this->generateExtractValue($attribute)})";
        }

        return 'return [' . implode(', ', $lines) . '];';
    }

    /**
     * Generate extract method's code for extract select attributes
     *
     * @return string
     */
    protected function generateExtractSelected()
    {
        $extracts = '';

        foreach ($this->resolver->rootAttributes() as $attribute) {
            $extracts .= <<<PHP
if (isset(\$attributes['{$attribute->name()}'])) {
    \$values['{$attribute->name()}'] = {$this->generateExtractValue($attribute)};
}

PHP;
        }

        return <<<PHP
\$attributes = array_flip(\$attributes);
\$values = [];

{$extracts}

return \$values;
PHP;
    }

    /**
     * Generate the extraction code for one attribute
     *
     * @param AttributeInfo $attribute
     *
     * @return string
     */
    protected function generateExtractValue(AttributeInfo $attribute)
    {
        $line = '';

        if ($attribute->isEmbedded()) {
            $varName = '$__rel_' . str_replace('.', '_', $attribute->name());
            $line .= '(' . $varName . ' = '.$this->accessor->getter('$object', $attribute->property()) . ") === null ? null : ";

            foreach ($attribute->embedded()->classes() as $class) {
                if ($this->resolver->isImportable($class)) {
                    $line .= "({$varName} instanceof {$this->normalizeClassName($class)} ? {$varName}->export() : ";
                } elseif ($this->resolver->isEntity($class)) {
                    $line .= "({$varName} instanceof {$this->normalizeClassName($class)} ? {$this->generateEmbeddedHydrator($class)}->extract({$varName}) : ";
                } else {
                    throw new HydratorException($class, 'Cannot generate embedded hydration for the property "'.$attribute->name().'"');
                }
            }

            $line .= $varName.str_repeat(')', count($attribute->embedded()->classes()));

            return $line;
        }

        $line .= $this->accessor->getter('$object', $attribute->property());

        return $line;
    }

    /**
     * Generate the flatExtract (i.e. Mapper::prepareToRepository) method body
     *
     * @return string
     */
    protected function generateFlatExtract()
    {
        return <<<PHP
if (empty(\$attributes)) {
{$this->code->indent($this->generateFlatExtractAll(), 1)}
} else {
{$this->code->indent($this->generateFlatExtractSelected(), 1)}
}
PHP;
    }

    /**
     * Generate extract method's code for extract all attributes
     *
     * @return string
     */
    protected function generateFlatExtractAll()
    {
        $lines = [];
        $embeddeds = [];

        foreach ($this->resolver->attributes() as $attribute) {
            if ($attribute->isEmbedded()) {
                $accessor = $this->accessors->embedded($attribute->embedded());

                $embeddeds[] = <<<PHP
{$accessor->getEmbedded('$__embedded')}
\$data['{$attribute->name()}'] = {$accessor->getter('$__embedded', $attribute->property())};
PHP;
            } else {
                $lines[] = "'{$attribute->name()}' => ({$this->accessor->getter('$object', $attribute->property())})";
            }
        }

        $lines = implode(', ', $lines);
        $embeddeds = implode(PHP_EOL, $embeddeds);

        return <<<PHP
\$data = [{$lines}];
{$embeddeds}

return \$data;
PHP;
    }

    /**
     * Generate the extract method's code for extract selected attributes
     *
     * @return string
     */
    protected function generateFlatExtractSelected()
    {
        $lines = '';

        foreach ($this->resolver->attributes() as $attribute) {
            if ($attribute->isEmbedded()) {
                $accessor = $this->accessors->embedded($attribute->embedded());

                $code = <<<PHP
{$accessor->getEmbedded('$__embedded')}
\$data['{$attribute->name()}'] = {$accessor->getter('$__embedded', $attribute->property())};
PHP;
            } else {
                $code = "\$data['{$attribute->name()}'] = {$this->accessor->getter('$object', $attribute->property())};";
            }

            $lines .= <<<PHP
if (isset(\$attributes['{$attribute->name()}'])) {
{$this->code->indent($code, 1)}
}

PHP;
        }

        return <<<PHP
\$data = [];

{$lines}

return \$data;
PHP;
    }

    /**
     * Generate the flatHydrate method's body
     *
     * @return string
     */
    protected function generateFlatHydrate()
    {
        $types = new TypeAccessor($this->code);

        $out = '';
        $set = [];
        $relationKeys = [];

        foreach ($this->mapper->relations() as $property => $metadata) {
            $relationKeys[$metadata['localKey']] = true;
        }

        foreach ($this->resolver->attributes() as $attribute) {
            $set[$attribute->field()] = $this->generateAttributeFlatHydrate($attribute, $relationKeys, $types);
        }


        foreach ($set as $field => $declaration) {
            $out .= <<<PHP
if (array_key_exists('{$field}', \$data)) {
{$this->code->indent($declaration, 1)}
}


PHP;
        }

        return $types->generateDeclaration().$out;
    }

    /**
     * Generate flat hydration for one attribute
     *
     * @param AttributeInfo $attribute
     * @param array $relationKeys
     * @param TypeAccessor $types
     *
     * @return string
     */
    protected function generateAttributeFlatHydrate(AttributeInfo $attribute, array $relationKeys, TypeAccessor $types)
    {
        $options = '';
        if ($attribute->phpOptions()) {
            $options = "\$this->__metadata->fields['{$attribute->field()}']['phpOptions']";
        }

        $target = "\$value";
        $out = $target.' = '.$types->generateFromDatabase($attribute->type(), '$data[\''.$attribute->field().'\']', $options);

        if (!$attribute->isEmbedded()) {
            return $out."\n".$this->accessor->setter('$object', $attribute->name(), $target, false).';';
        }

        return $this->code->lines([
            $out,
            $this->accessors
                ->embedded($attribute->embedded())
                ->fullSetter($attribute->property(), $target, '$__embedded', '$data').';'
        ]);
    }

    /**
     * Generate the extractOne() method's body
     *
     * @return string
     */
    protected function generateExtractOneBody()
    {
        $cases = [];

        foreach ($this->resolver->attributes() as $attribute) {
            $cases[$attribute->name()] = $this->generateExtractOneCaseAttribute($attribute);
        }

        foreach ($this->resolver->embeddeds() as $embedded) {
            $cases[$embedded->path()] = $this->generateExtractOneCaseEmbedded($embedded);
        }

        return $this->code->switch(
            '$attribute',
            $cases,
            <<<'PHP'
throw new \InvalidArgumentException('Cannot read from attribute "'.$attribute.'" : it\'s not declared');
PHP
        );
    }

    /**
     * Generate one case for extractOne switch, for attribute
     *
     * @param AttributeInfo $attribute
     *
     * @return string
     */
    protected function generateExtractOneCaseAttribute(AttributeInfo $attribute)
    {
        if (!$attribute->isEmbedded()) {
            return "return {$this->accessor->getter('$object', $attribute->property())};";
        }

        $accessor = $this->accessors->embedded($attribute->embedded());

        return $accessor->getEmbedded('$__embedded').$this->code->eol().'return '.$accessor->getter('$__embedded', $attribute->property()).';';
    }

    /**
     * Generate one case for extractOne switch, for embedded
     *
     * @param EmbeddedInfo $embedded
     *
     * @return string
     */
    protected function generateExtractOneCaseEmbedded(EmbeddedInfo $embedded)
    {
        $varName = '$__' . str_replace('.', '_', $embedded->path());
        $code = $this->accessors->embedded($embedded)->getEmbedded($varName, false);

        return $this->code->lines([$code, 'return '.$varName.';']);
    }

    /**
     * Generate hydrateOne() method's body
     *
     * @return string
     */
    protected function generateHydrateOneBody()
    {
        $cases = [];

        foreach ($this->resolver->attributes() as $attribute) {
            $cases[$attribute->name()] = $this->generateHydrateOneCaseAttribute($attribute);
        }

        foreach ($this->resolver->embeddeds() as $embedded) {
            $cases[$embedded->path()] = $this->generateHydrateOneCaseEmbedded($embedded);
        }

        return $this->code->switch(
            '$attribute',
            $cases,
            <<<'PHP'
throw new \InvalidArgumentException('Cannot write to attribute "'.$attribute.'" : it\'s not declared');
PHP

        );
    }

    /**
     * Generate one case for hydrateOne switch, for attribute
     *
     * @todo Exception on hydrate unresolved embedded attribute (like MapperHydrator)
     *
     * @param AttributeInfo $attribute
     *
     * @return string
     */
    protected function generateHydrateOneCaseAttribute($attribute)
    {
        if (!$attribute->isEmbedded()) {
            return $this->accessor->setter('$object', $attribute->property(), '$value', false).';';
        }

        return $this->accessors
                ->embedded($attribute->embedded())
                ->fullSetter($attribute->property(), '$value', '$__embedded').';'
            ;
    }

    /**
     * Generate one case for hydrateOne switch, for embedded
     *
     * @param EmbeddedInfo $embedded
     *
     * @return string
     */
    protected function generateHydrateOneCaseEmbedded(EmbeddedInfo $embedded)
    {
        if ($embedded->isRoot()) {
            return $this->accessor->setter('$object', $embedded->path(), '$value', false).';';
        }

        return $this->accessors
                ->embedded($embedded->parent())
                ->fullSetter($embedded->property(), '$value').';'
            ;
    }
}
