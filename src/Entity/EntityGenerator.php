<?php

namespace Bdf\Prime\Entity;

use Bdf\Prime\Mapper\Info\InfoInterface;
use Bdf\Prime\Mapper\Info\MapperInfo;
use Bdf\Prime\Mapper\Info\ObjectPropertyInfo;
use Bdf\Prime\Mapper\Info\PropertyInfo;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\ServiceLocator;
use Bdf\Prime\Types\PhpTypeInterface;
use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\Inflector as InflectorObject;
use Doctrine\Inflector\InflectorFactory;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use Nette\PhpGenerator\PsrPrinter;

/**
 * Generic class used to generate PHP 7 and 8 entity classes from Mapper.
 *
 *     [php]
 *     $mapper = $service->mappers()->build('Entity');
 *
 *     $generator = new EntityGenerator();
 *     $generator->setGenerateStubMethods(true);
 *     $generator->setRegenerateEntityIfExists(false);
 *     $generator->setUpdateEntityIfExists(true);
 *     $generator->generate($mapper, '/path/to/generate/entities');
 *
 * @todo version 3.6 et 4.0 pour nette generator
 */
class EntityGenerator
{
    // @todo should not be there : should be on PhpTypeInterface
    /**
     * Map prime types to php 7.4 property type
     */
    public const PROPERTY_TYPE_MAP = [
        PhpTypeInterface::BOOLEAN => 'bool',
        PhpTypeInterface::DOUBLE => 'float',
        PhpTypeInterface::INTEGER => 'int',
    ];

    /**
     * Specifies class fields should be protected.
     */
    public const FIELD_VISIBLE_PROTECTED = ClassType::VISIBILITY_PROTECTED;

    /**
     * Specifies class fields should be private.
     */
    public const FIELD_VISIBLE_PRIVATE = ClassType::VISIBILITY_PRIVATE;

    /**
     * The prime service locator
     *
     * @var ServiceLocator
     */
    private ServiceLocator $prime;

    /**
     * The inflector instance
     *
     * @var InflectorObject
     */
    private InflectorObject $inflector;

    /**
     * The mapper info
     *
     * @var MapperInfo
     */
    private MapperInfo $mapperInfo;

    /**
     * The extension to use for written php files.
     *
     * @var string
     */
    private string $extension = '.php';

    /**
     * Whether or not the current Mapper instance is new or old.
     *
     * @var boolean
     */
    private bool $isNew = true;

    /**
     * @var array<class-string, array{properties: list<string>, methods: list<string>}>
     */
    private array $staticReflection = [];

    /**
     * Number of spaces to use for indention in generated code.
     */
    private int $numSpaces = 4;

    /**
     * The class all generated entities should extend.
     *
     * @var class-string|null
     */
    private ?string $classToExtend = null;

    /**
     * The interfaces all generated entities should implement.
     *
     * @var array<class-string, class-string>
     */
    private array $interfaces = [];

    /**
     * The traits
     *
     * @var array<string, string>
     */
    private array $traits = [];

    /**
     * Whether or not to generate sub methods.
     *
     * @var boolean
     */
    private bool $generateEntityStubMethods = true;

    /**
     * Whether or not to update the entity class if it exists already.
     *
     * @var boolean
     */
    private bool $updateEntityIfExists = false;

    /**
     * Whether or not to re-generate entity class if it exists already.
     *
     * @var boolean
     */
    private bool $regenerateEntityIfExists = false;

    /**
     * The name of get methods will not contains the 'get' prefix
     *
     * @var boolean
     */
    private bool $useGetShortcutMethod = true;

    /**
     * Visibility of the field
     *
     * @var self::FIELD_*
     */
    private string $fieldVisibility = self::FIELD_VISIBLE_PROTECTED;

    /**
     * Use type on generated properties
     * Note: only compatible with PHP >= 7.4
     *
     * @var bool
     */
    private bool $useTypedProperties = false;

    /**
     * Enable generation of PHP 8 constructor with promoted properties
     * If used, the constructor will not call import for filling the entity
     *
     * Note: only compatible with PHP >= 8.0
     *
     * @var bool
     */
    private bool $useConstructorPropertyPromotion = false;

    /**
     * Set prime service locator
     */
    public function __construct(ServiceLocator $prime, ?InflectorObject $inflector = null)
    {
        $this->prime = $prime;
        $this->inflector = $inflector ?? InflectorFactory::create()->build();
    }

    /**
     * Generates and writes entity classes
     *
     * @param Mapper $mapper
     * @param string|null $file Entity file name
     *
     * @return string|false If no generation
     *
     * @api
     */
    public function generate(Mapper $mapper, ?string $file = null)
    {
        $this->isNew = !$file || !file_exists($file) || $this->regenerateEntityIfExists;

        // If entity doesn't exist or we're re-generating the entities entirely
        if ($this->isNew || !$file) {
            return $this->generateEntityClass($mapper);
        // If entity exists and we're allowed to update the entity class
        } elseif ($this->updateEntityIfExists) {
            return $this->generateUpdatedEntityClass($mapper, $file);
        }

        return false;
    }

    /**
     * Generates a PHP5 Doctrine 2 entity class from the given Mapper instance.
     *
     * @param Mapper $mapper
     *
     * @return string
     */
    public function generateEntityClass(Mapper $mapper): string
    {
        $this->mapperInfo = $mapper->info();

        $file = new PhpFile();

        $namespace = $file->addNamespace($this->hasNamespace($this->mapperInfo->className()) ? $this->getNamespace($this->mapperInfo->className()) : '');
        $generator = $namespace->addClass($this->getClassName($this->mapperInfo->className()));

        $this->staticReflection[$this->mapperInfo->className()] = ['properties' => [], 'methods' => []];

        $this->generateEntityClassDeclaration($generator);
        $this->generateEntityUse($namespace);
        $this->generateEntityBody($generator, $namespace);

        return (new ConfigurableEntityPrinter($this))
            ->printFile($file);
    }

    /**
     * Generates the updated code for the given Mapper and entity at path.
     *
     * @param Mapper $mapper
     * @param string $filename
     *
     * @return string
     */
    public function generateUpdatedEntityClass(Mapper $mapper, string $filename): string
    {
        $this->mapperInfo = $mapper->info();

        $currentCode = file_get_contents($filename);
        $file = PhpFile::fromCode($currentCode);
        $generator = $file->getClasses()[$this->mapperInfo->className()];
        $namespace = $file->getNamespaces()[$this->getNamespace($this->mapperInfo->className())];

        $this->parseTokensInEntityFile($currentCode);

        $this->generateEntityBody($generator, $namespace);

        return (new ConfigurableEntityPrinter($this))
            ->printFile($file);
    }

    /**
     * Generate class inheritance and traits
     */
    protected function generateEntityClassDeclaration(ClassType $generator): void
    {
        $generator->addComment($this->getClassName($this->mapperInfo->className()));

        if ($this->classToExtend) {
            if (method_exists($generator, 'setExtends')) {
                $generator->setExtends($this->classToExtend);
            } else {
                $generator->addExtend($this->classToExtend);
            }
        }

        foreach ($this->interfaces as $interface) {
            $generator->addImplement($interface);
        }

        $generator->setTraits($this->traits);
    }

    /**
     * Generate use part
     */
    protected function generateEntityUse(PhpNamespace $namespace): void
    {
        if (($parentClass = $this->getClassToExtend()) && $this->hasNamespace($parentClass)) {
            $namespace->addUse($parentClass);
        }

        foreach ($this->interfaces as $interface) {
            if ($this->hasNamespace($interface)) {
                $namespace->addUse($interface);
            }
        }

        foreach ($this->traits as $trait) {
            if ($this->hasNamespace($trait)) {
                $namespace->addUse($trait);
            }
        }

        foreach ($this->mapperInfo->objects() as $info) {
            $className = $info->className();
            if (!$info->belongsToRoot()) {
                continue;
            }

            if ($this->hasNamespace($className) && $this->getNamespace($className) !== $namespace->getName()) {
                $namespace->addUse($className);
            }

            if ($info->wrapper() !== null) {
                $repository = $this->prime->repository($className);
                $wrapperClass = $repository->collectionFactory()->wrapperClass($info->wrapper());

                if ($this->hasNamespace($wrapperClass)) {
                    $namespace->addUse($wrapperClass);
                }
            }
        }
    }

    protected function generateEntityBody(ClassType $generator, PhpNamespace $namespace): void
    {
        $properties = [
            ...$this->generateEntityFieldMappingProperties($this->useConstructorPropertyPromotion),
            ...$this->generateEntityEmbeddedProperties($namespace, $this->useConstructorPropertyPromotion)
        ];

        if (!$this->useConstructorPropertyPromotion) {
            foreach ($properties as $property) {
                $property->addProperty($generator, $namespace);
            }
        }

        if ($this->generateEntityStubMethods) {
            $this->generateEntityStubMethods($generator, $namespace);
        }

        $this->generateEntityConstructor($generator, $namespace, $this->useConstructorPropertyPromotion, $properties);
    }

    /**
     * @param bool $propertyPromotion Generate constructor with property promotion
     * @param list<PropertyGenerator> $properties
     */
    protected function generateEntityConstructor(ClassType $generator, PhpNamespace $namespace, bool $propertyPromotion, array $properties): void
    {
        $initializable = in_array(InitializableInterface::class, $this->interfaces);
        $isImportable  = in_array(ImportableInterface::class, $this->interfaces)
                    || is_subclass_of($this->classToExtend, ImportableInterface::class);

        if (!$this->hasMethod('__construct')) {
            if ($propertyPromotion) {
                $this->generateConstructorWithPromotedProperties($generator, $namespace, $initializable, $properties);
            } else {
                $this->generateClassicConstructor($generator, $namespace, $isImportable, $initializable, $properties);
            }
        }

        if (!$this->hasMethod('initialize') && $initializable) {
            $init = Method::from([InitializableInterface::class, 'initialize'])
                ->addComment('{@inheritdoc}');

            foreach ($properties as $property) {
                $property->addInitializeLine($init);
            }

            $generator->addMember($init);
        }
    }

    /**
     * Generate PHP 8 constructor
     *
     * @param bool $initializable Does the entity class implements InitializableInterface ?
     * @param list<PropertyGenerator> $properties Properties to declare an initialize
     */
    private function generateConstructorWithPromotedProperties(ClassType $generator, PhpNamespace $namespace, bool $initializable, array $properties): void
    {
        $constructor = $generator->addMethod('__construct');

        foreach ($properties as $property) {
            $property->addPromotedProperty($constructor, $namespace);
        }

        if ($initializable) {
            $constructor->addBody('$this->initialize();');
        } else {
            foreach ($properties as $property) {
                // Assignment operator : use null coalesce assignment with property promotion
                // because assignation is performed before initializing default value
                $property->addInitializeLine($constructor, '??=');
            }
        }
    }

    /**
     * Generate classic constructor
     *
     * @param bool $isImportable Does the entity class implements InitializableInterface ?
     * @param bool $initializable Does the entity class implements ImportableInterface ?
     * @param list<PropertyGenerator> $properties Properties to initialize
     */
    private function generateClassicConstructor(ClassType $generator, PhpNamespace $namespace, bool $isImportable, bool $initializable, array $properties): void
    {
        if ($isImportable) {
            $constructor = $generator->addMethod('__construct');

            $constructor
                ->addParameter('data', [])
                ->setType('array')
            ;

            if ($initializable) {
                $constructor->addBody('$this->initialize();');
            } else {
                foreach ($properties as $property) {
                    $property->addInitializeLine($constructor);
                }
            }

            $constructor->addBody('$this->import($data);');
        } elseif (!$initializable) {
            $constructor = null;

            foreach ($properties as $property) {
                if ($property->hasInitialisation()) {
                    // Add a constructor only if it's necessary
                    $constructor = $constructor ?? $generator->addMethod('__construct');
                    $property->addInitializeLine($constructor);
                }
            }
        }
    }

    protected function generateEntityStubMethods(ClassType $generator, PhpNamespace $namespace): void
    {
        foreach ($this->mapperInfo->properties() as $property) {
            $this->generateSetter($generator, $namespace, $property);
            $this->generateGetter($generator, $namespace, $property);
        }

        foreach ($this->mapperInfo->objects() as $property) {
            if (!$property->belongsToRoot()) {
                continue;
            }

            if ($property->isArray() && $property->wrapper() === null) {
                $this->generateAdder($generator, $namespace, $property);
            }

            $this->generateSetter($generator, $namespace, $property);
            $this->generateGetter($generator, $namespace, $property);
        }
    }

    /**
     * @param bool $forceNullable Force typehint to be nullable. Useful property promotion
     * @return list<PropertyGenerator>
     */
    protected function generateEntityFieldMappingProperties(bool $forceNullable = false): array
    {
        $properties = [];

        foreach ($this->mapperInfo->properties() as $property) {
            if ($this->hasProperty($property->name())) {
                continue;
            }

            $properties[] = $generator = new PropertyGenerator($property->name());

            $generator->setNullable($forceNullable || $property->isNullable());
            $generator->setVisibility($this->fieldVisibility);
            $generator->setVarTag($property->phpType());

            if ($this->useTypedProperties) {
                $generator->setTypeHint($property->phpType());
            }

            if ($property->hasDefault() && !$property->isDateTime()) {
                $generator->setDefaultValue($property->convert($property->getDefault()));
            } elseif ($property->isArray()) {
                $generator->setDefaultValue([]);
            } elseif (($forceNullable || ($this->useTypedProperties && $property->isNullable()))) {
                // A nullable property should be defined as null by default
                // A property is considered as nullable if it's explicitly defined on mapper or if the field is auto-generated
                $generator->setDefaultValue(null);
            }

            if ($property->hasDefault() && $property->isDateTime()) {
                $constructorArgs = '';
                // Add the default timezone from the property type.
                if ($timezone = $property->getTimezone()) {
                    $constructorArgs = "'now', new \DateTimeZone('$timezone')";
                }

                $generator->setInitialize('new '.$property->phpType().'('.$constructorArgs.')');
            }
        }

        return $properties;
    }

    /**
     * @param bool $forceNullable Force typehint to be nullable. Useful property promotion
     * @return list<PropertyGenerator>
     */
    protected function generateEntityEmbeddedProperties(PhpNamespace $namespace, bool $forceNullable = false): array
    {
        $properties = [];

        foreach ($this->mapperInfo->objects() as $property) {
            if (!$property->belongsToRoot() || $this->hasProperty($property->name())) {
                continue;
            }

            $properties[] = $generator = new PropertyGenerator($property->name());
            $generator->setVisibility($this->fieldVisibility);

            // Embedded property : should not be null
            if (!$property->isRelation()) {
                $generator->setNullable($forceNullable);
                $generator->setVarTag($property->className());
                $generator->setInitialize('new '.$namespace->simplifyName($property->className()).'()');

                if ($this->useTypedProperties) {
                    $generator->setTypeHint($property->className());
                }

                continue;
            }

            $generator->setNullable($nullable = $forceNullable || $this->useTypedProperties);

            switch(true) {
                case $property->isArray() && $property->wrapper() === null:
                    // Simple array relation
                    $generator->setDefaultValue([]);
                    $generator->setVarTag($property->className() . '[]');

                    if ($this->useTypedProperties) {
                        $generator->setTypeHint('array');
                    }
                    break;

                case $property->isArray() && $property->wrapper() !== null:
                    // Array relation with wrapper
                    $repository = $this->prime->repository($property->className());
                    $generator->setVarTag($repository->collectionFactory()->wrapperClass($property->wrapper()) . '|' . $property->className() . '[]');

                    // The value is an object : so the default value must be null
                    if ($nullable) {
                        $generator->setDefaultValue(null);
                    }

                    if ($this->useTypedProperties) {
                        $generator->setTypeHint($repository->collectionFactory()->wrapperClass($property->wrapper()));
                    }

                    // @todo handle other wrapper types
                    if ($property->wrapper() === 'collection') {
                        $generator->setInitialize($namespace->simplifyName($property->className()).'::collection()');
                    }

                    break;

                default:
                    // Simple relation
                    $generator->setVarTag($property->className());
                    $generator->setInitialize('new '.$namespace->simplifyName($property->className()).'()');

                    // The value is an object : so the default value must be null
                    if ($nullable) {
                        $generator->setDefaultValue(null);
                    }

                    if ($this->useTypedProperties) {
                        $generator->setTypeHint($property->className());
                    }
            }
        }

        return $properties;
    }

    /**
     * Get accessor metadata for a given property
     *
     * @param PhpNamespace $namespace
     * @param InfoInterface $propertyInfo
     * @param string|null $prefix Accessor prefix. Can be null to use the field name as method name.
     * @param bool $one In case of array property, get metadata for single item instead of the whole array.
     *
     * @return array{method: string, variable: string, field: string, typeHint: string, docType: string, nullable: bool}|null Accessor metadata, or null if the method already exists.
     */
    protected function accessorMetadata(PhpNamespace $namespace, InfoInterface $propertyInfo, ?string $prefix, bool $one = false): ?array
    {
        $fieldName = $propertyInfo->name();

        if (!$prefix) {
            $variableName = $this->inflector->camelize($fieldName);
            $methodName = $variableName;
        } else {
            $methodName = $prefix . $this->inflector->classify($fieldName);
            $variableName = $this->inflector->camelize($fieldName);
        }

        if ($one) {
            $methodName = $this->inflector->singularize($methodName);
            $variableName = $this->inflector->singularize($variableName);
        }

        if ($this->hasMethod($methodName)) {
            return null;
        }

        $this->staticReflection[$this->mapperInfo->className()]['methods'][] = strtolower($methodName);

        if ($propertyInfo->isObject()) {
            /** @var ObjectPropertyInfo $propertyInfo */
            $variableType = $namespace->simplifyName($propertyInfo->className());
            // Only makes nullable for single relation
            $methodTypeHint = $propertyInfo->className();
            $nullable = (!$one && !$propertyInfo->isEmbedded());
        } else {
            /** @var PropertyInfo $propertyInfo */
            $variableType = $propertyInfo->phpType();
            $methodTypeHint = self::PROPERTY_TYPE_MAP[$variableType] ?? $variableType;
            $nullable = $propertyInfo->isNullable();
        }

        if ($propertyInfo->isArray() && $one === false) {
            if ($propertyInfo->isObject() && $propertyInfo->wrapper() !== null) {
                /** @var ObjectPropertyInfo $propertyInfo */
                $repository = $this->prime->repository($propertyInfo->className());

                $methodTypeHint = $repository->collectionFactory()->wrapperClass($propertyInfo->wrapper());
                $variableType .= '[]|'.$namespace->simplifyName($methodTypeHint);
            } else {
                $methodTypeHint = 'array';

                if ($variableType !== 'array') {
                    $variableType .= '[]';
                }
            }
        }

        return [
            'field' => $fieldName,
            'variable' => $variableName,
            'method' => $methodName,
            'typeHint' => $methodTypeHint,
            'docType' => $variableType,
            'nullable' => $nullable,
        ];
    }

    protected function generateGetter(ClassType $generator, PhpNamespace $namespace, InfoInterface $propertyInfo): void
    {
        $metadata = $this->accessorMetadata($namespace, $propertyInfo, $this->useGetShortcutMethod ? null : 'get');

        if (!$metadata) {
            return;
        }

        $method = $generator->addMethod($metadata['method']);
        $method->addComment('Get ' . $metadata['variable']);
        $method->addComment('');
        $method->setReturnType($metadata['typeHint']);
        $method->setReturnNullable($metadata['nullable']);
        $method->setBody('return $this->?;', [$metadata['field']]);
        $method->addComment('@return ' . $metadata['docType']);
    }

    protected function generateSetter(ClassType $generator, PhpNamespace $namespace, InfoInterface $propertyInfo): void
    {
        $metadata = $this->accessorMetadata($namespace, $propertyInfo, 'set');

        if (!$metadata) {
            return;
        }

        $method = $generator->addMethod($metadata['method']);
        $method->addComment('Set ' . $metadata['variable']);
        $method->addComment('');
        $method->addComment('@param ' . $metadata['docType'] . ' $' . $metadata['variable']);
        $method->addComment('');
        $method->addComment('@return $this');
        $method->setReturnType('self');
        $method
            ->addParameter($metadata['variable'])
            ->setType($metadata['typeHint'])
            ->setNullable($metadata['nullable'])
        ;
        $method->addBody('$this->? = $?;', [$metadata['field'], $metadata['variable']]);
        $method->addBody('');
        $method->addBody('return $this;');
    }

    protected function generateAdder(ClassType $generator, PhpNamespace $namespace, InfoInterface $propertyInfo): void
    {
        $metadata = $this->accessorMetadata($namespace, $propertyInfo, 'add', true);

        if (!$metadata) {
            return;
        }

        $method = $generator->addMethod($metadata['method']);
        $method->addComment('Add ' . $metadata['variable']);
        $method->addComment('');
        $method->addComment('@param ' . $metadata['docType'] . ' $' . $metadata['variable']);
        $method->addComment('');
        $method->addComment('@return $this');
        $method->setReturnType('self');
        $method
            ->addParameter($metadata['variable'])
            ->setType($metadata['typeHint'])
            ->setNullable($metadata['nullable'])
        ;
        $method->addBody('$this->?[] = $?;', [$metadata['field'], $metadata['variable']]);
        $method->addBody('');
        $method->addBody('return $this;');
    }

    //
    //---------- tools methods
    //

    /**
     * @todo this won't work if there is a namespace in brackets and a class outside of it.
     *
     * @param string $src
     *
     * @return void
     */
    protected function parseTokensInEntityFile(string $src): void
    {
        $tokens = token_get_all($src);
        $lastSeenNamespace = "";
        /* @var class-string $lastSeenClass */
        $lastSeenClass = null;

        $inNamespace = false;
        $inClass = false;

        for ($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            if (in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])) {
                continue;
            }

            if ($inNamespace) {
                if ($token[0] == T_NS_SEPARATOR || $token[0] == T_STRING || (defined('T_NAME_QUALIFIED') && $token[0] == T_NAME_QUALIFIED)) {
                    $lastSeenNamespace .= $token[1];
                } elseif (is_string($token) && in_array($token, [';', '{'])) {
                    $inNamespace = false;
                }
            }

            if ($inClass) {
                $inClass = false;
                $lastSeenClass = $lastSeenNamespace . ($lastSeenNamespace ? '\\' : '') . $token[1];
                $this->staticReflection[$lastSeenClass]['properties'] = [];
                $this->staticReflection[$lastSeenClass]['methods'] = [];
            }

            if ($token[0] == T_NAMESPACE) {
                $lastSeenNamespace = "";
                $inNamespace = true;
            } elseif ($token[0] == T_CLASS && $tokens[$i-1][0] != T_DOUBLE_COLON) {
                $inClass = true;
            } elseif ($token[0] == T_FUNCTION) {
                if ($tokens[$i+2][0] == T_STRING) {
                    $this->staticReflection[$lastSeenClass]['methods'][] = strtolower($tokens[$i+2][1]);
                } elseif ($tokens[$i+2] == "&" && $tokens[$i+3][0] == T_STRING) {
                    $this->staticReflection[$lastSeenClass]['methods'][] = strtolower($tokens[$i+3][1]);
                }
            } elseif (in_array($token[0], [T_VAR, T_PUBLIC, T_PRIVATE, T_PROTECTED]) && $tokens[$i+2][0] != T_FUNCTION) {
                $this->staticReflection[$lastSeenClass]['properties'][] = substr($tokens[$i+2][1], 1);
            }
        }
    }

    /**
     * @param string $property
     *
     * @return bool
     */
    protected function hasProperty(string $property): bool
    {
        if ($this->classToExtend) {
            // don't generate property if its already on the base class.
            $reflClass = new \ReflectionClass($this->getClassToExtend());
            if ($reflClass->hasProperty($property)) {
                return true;
            }
        }

        // check traits for existing property
        foreach ($this->getTraitsReflections() as $trait) {
            if ($trait->hasProperty($property)) {
                return true;
            }
        }

        return (
            isset($this->staticReflection[$this->mapperInfo->className()]) &&
            in_array($property, $this->staticReflection[$this->mapperInfo->className()]['properties'])
        );
    }

    /**
     * @param string $method
     *
     * @return bool
     */
    protected function hasMethod(string $method): bool
    {
        if ($this->classToExtend || (!$this->isNew && class_exists($this->mapperInfo->className()))) {
            // don't generate method if its already on the base class.
            $reflClass = new \ReflectionClass($this->getClassToExtend() ?: $this->mapperInfo->className());

            if ($reflClass->hasMethod($method)) {
                return true;
            }
        }

        // check traits for existing method
        foreach ($this->getTraitsReflections() as $trait) {
            if ($trait->hasMethod($method)) {
                return true;
            }
        }

        return (
            isset($this->staticReflection[$this->mapperInfo->className()]) &&
            in_array(strtolower($method), $this->staticReflection[$this->mapperInfo->className()]['methods'])
        );
    }

    /**
     * Get the class short name
     *
     * @param string $className
     *
     * @return string
     */
    protected function getClassName(string $className): string
    {
        $parts = explode('\\', $className);
        return array_pop($parts);
    }

    /**
     * @param string $className
     *
     * @return string
     */
    protected function getNamespace(string $className): string
    {
        $parts = explode('\\', $className);
        array_pop($parts);

        return implode('\\', $parts);
    }

    /**
     * @param string $className
     *
     * @return bool
     */
    protected function hasNamespace(string $className): bool
    {
        return strrpos($className, '\\') != 0;
    }

    /**
     * @return array<trait-string, \ReflectionClass>
     */
    protected function getTraitsReflections(): array
    {
        if ($this->isNew) {
            return [];
        }

        $reflClass = new \ReflectionClass($this->mapperInfo->className());

        $traits = [];

        while ($reflClass !== false) {
            $traits = array_merge($traits, $reflClass->getTraits());

            $reflClass = $reflClass->getParentClass();
        }

        return $traits;
    }

    //---------------------- mutators

    /**
     * Sets the number of spaces the exported class should have.
     *
     * @api
     */
    public function setNumSpaces(int $numSpaces): void
    {
        $this->numSpaces = $numSpaces;
    }

    /**
     * Gets the indentation spaces
     */
    public function getNumSpaces(): int
    {
        return $this->numSpaces;
    }

    /**
     * Sets the extension to use when writing php files to disk.
     *
     * @api
     */
    public function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }

    /**
     * Get the file extension
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * Sets the name of the class the generated classes should extend from.
     *
     * @api
     */
    public function setClassToExtend(string $classToExtend): void
    {
        $this->classToExtend = $classToExtend;
    }

    /**
     * Get the class to extend
     */
    public function getClassToExtend(): ?string
    {
        return $this->classToExtend;
    }

    /**
     * Add interface to implement
     *
     * @api
     *
     * @return void
     */
    public function addInterface(string $interface): void
    {
        $this->interfaces[$interface] = $interface;
    }

    /**
     * Sets the interfaces
     *
     * @param string[] $interfaces
     *
     * @api
     */
    public function setInterfaces(array $interfaces): void
    {
        $this->interfaces = $interfaces;
    }

    /**
     * Get the registered interfaces
     */
    public function getInterfaces(): array
    {
        return $this->interfaces;
    }

    /**
     * Add trait
     *
     * @api
     *
     * @return void
     */
    public function addTrait(string $trait): void
    {
        $this->traits[$trait] = $trait;
    }

    /**
     * Sets the traits
     *
     * @param string[] $traits
     *
     * @api
     */
    public function setTraits(array $traits): void
    {
        $this->traits = $traits;
    }

    /**
     * Get the registered traits
     */
    public function getTraits(): array
    {
        return $this->traits;
    }

    /**
     * Sets the class fields visibility for the entity (can either be private or protected).
     *
     * @throws \InvalidArgumentException
     *
     * @api
     */
    public function setFieldVisibility(string $visibility): void
    {
        if ($visibility !== static::FIELD_VISIBLE_PRIVATE && $visibility !== static::FIELD_VISIBLE_PROTECTED) {
            throw new \InvalidArgumentException('Invalid provided visibility (only private and protected are allowed): ' . $visibility);
        }

        $this->fieldVisibility = $visibility;
    }

    /**
     * Get the field visibility
     */
    public function getFieldVisibility(): string
    {
        return $this->fieldVisibility;
    }

    /**
     * Sets whether or not to try and update the entity if it already exists.
     *
     * @api
     */
    public function setUpdateEntityIfExists(bool $bool): void
    {
        $this->updateEntityIfExists = $bool;
    }

    /**
     * Get the flag for updating the entity
     */
    public function getUpdateEntityIfExists(): bool
    {
        return $this->updateEntityIfExists;
    }

    /**
     * Sets whether or not to regenerate the entity if it exists.
     *
     * @api
     */
    public function setRegenerateEntityIfExists(bool $bool): void
    {
        $this->regenerateEntityIfExists = $bool;
    }

    /**
     * Get the flag for regenerating entity
     */
    public function getRegenerateEntityIfExists(): bool
    {
        return $this->regenerateEntityIfExists;
    }

    /**
     * Sets whether or not to generate stub methods for the entity.
     *
     * @api
     */
    public function setGenerateStubMethods(bool $bool): void
    {
        $this->generateEntityStubMethods = $bool;
    }

    /**
     * Get the flag for generating stub methods
     */
    public function getGenerateStubMethods(): bool
    {
        return $this->generateEntityStubMethods;
    }

    /**
     * Sets whether or not the get mehtod will be suffixed by 'get'.
     *
     * @param bool $flag
     *
     * @return void
     *
     * @api
     */
    public function useGetShortcutMethod(bool $flag = true)
    {
        $this->useGetShortcutMethod = $flag;
    }

    /**
     * Get the flag for get mehtod name.
     */
    public function getUseGetShortcutMethod(): bool
    {
        return $this->useGetShortcutMethod;
    }

    /**
     * @return bool
     */
    public function getUseTypedProperties(): bool
    {
        return $this->useTypedProperties;
    }

    /**
     * Enable usage of php 7.4 type properties
     *
     * @param bool $useTypedProperties
     */
    public function useTypedProperties(bool $useTypedProperties = true): void
    {
        $this->useTypedProperties = $useTypedProperties;
    }

    /**
     * @return bool
     */
    public function getUseConstructorPropertyPromotion(): bool
    {
        return $this->useConstructorPropertyPromotion;
    }

    /**
     * Enable usage of PHP 8 promoted properties on constructor instead of array import
     *
     * @param bool $useConstructorPropertyPromotion
     */
    public function useConstructorPropertyPromotion(bool $useConstructorPropertyPromotion = true): void
    {
        $this->useConstructorPropertyPromotion = $useConstructorPropertyPromotion;
    }
}

/**
 * @internal
 */
class PropertyGenerator
{
    private string $name;
    private ?string $typeHint = null;
    private bool $nullable = false;
    private ?string $varTag = null;
    private string $visibility = EntityGenerator::FIELD_VISIBLE_PROTECTED;
    private $defaultValue;
    private bool $hasDefaultValue = false;
    private ?string $initialize = null;

    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function setTypeHint(string $typeHint): void
    {
        $this->typeHint = $typeHint;
    }

    public function setNullable(bool $nullable): void
    {
        $this->nullable = $nullable;
    }

    public function setVarTag(string $varTag): void
    {
        $this->varTag = $varTag;
    }

    public function setVisibility(string $visibility): void
    {
        $this->visibility = $visibility;
    }

    public function setDefaultValue($value): void
    {
        $this->defaultValue = $value;
        $this->hasDefaultValue = true;
    }

    public function setInitialize(string $initialize): void
    {
        $this->initialize = $initialize;
    }

    public function hasInitialisation(): bool
    {
        return $this->initialize !== null;
    }

    public function addProperty(ClassType $generator, ?PhpNamespace $namespace): void
    {
        $property = $generator->addProperty($this->name);

        $property
            ->setNullable($this->nullable)
            ->setVisibility($this->visibility)
        ;

        if ($this->typeHint) {
            $typehint = EntityGenerator::PROPERTY_TYPE_MAP[$this->typeHint] ?? $this->typeHint;
            $property->setType($typehint);
        }

        if ($this->hasDefaultValue) {
            $property->setValue($this->defaultValue);
        }

        if ($this->varTag) {
            $type = $this->varTag;

            if (!isset(EntityGenerator::PROPERTY_TYPE_MAP[$this->varTag]) && $namespace) {
                $type = $this->simplifyType($type, $namespace);
            }

            $property->addComment("\n@var $type");
        }
    }

    public function addPromotedProperty(Method $constructor, ?PhpNamespace $namespace): void
    {
        $parameter = $constructor->addPromotedParameter($this->name);

        $parameter
            ->setNullable($this->nullable)
            ->setVisibility($this->visibility)
        ;

        if ($this->typeHint) {
            $typehint = EntityGenerator::PROPERTY_TYPE_MAP[$this->typeHint] ?? $this->typeHint;
            $parameter->setType($typehint);
        }

        if ($this->hasDefaultValue) {
            $parameter->setDefaultValue($this->defaultValue);
        }

        if ($this->varTag) {
            $type = $this->varTag;

            if (!isset(EntityGenerator::PROPERTY_TYPE_MAP[$this->varTag]) && $namespace) {
                $type = $this->simplifyType($type, $namespace);
            }

            $parameter->addComment("\n@var $type");
        }
    }

    public function addInitializeLine(Method $initializeMethod, string $assignationOperator = '='): void
    {
        if ($this->initialize) {
            $initializeMethod->addBody('$this->'.$this->name.' '.$assignationOperator.' '.$this->initialize.';');
        }
    }

    private function simplifyType(string $type, PhpNamespace $namespace): string
    {
        $types = explode('|', $type);

        foreach ($types as &$part) {
            $atomicType = $part;
            $isArray = false;

            if (str_ends_with($atomicType, '[]')) {
                $atomicType = substr($atomicType, 0, -2);
                $isArray = true;
            }

            if (isset(EntityGenerator::PROPERTY_TYPE_MAP[$atomicType])) {
                continue;
            }

            $part = $namespace->simplifyName($atomicType) . ($isArray ? '[]' : '');
        }

        return implode('|', $types);
    }
}

/**
 * @internal
 */
class ConfigurableEntityPrinter extends Printer
{
    public function __construct(EntityGenerator $generator)
    {
        parent::__construct();

        $this->linesBetweenMethods = 1;
        $this->linesBetweenProperties = 1;
        $this->indentation = str_repeat(' ', $generator->getNumSpaces());
    }

    public function printClass($class, ?PhpNamespace $namespace = null): string
    {
        $code = parent::printClass($class, $namespace);

        // Reformat property docblock : nette will generate property doc on single line
        return preg_replace('#^( *)/\*\*(.*)\s+\*/$#m', "$1/**\n$1 *$2\n$1 */", $code);
    }
}
