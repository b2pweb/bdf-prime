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
use Nette\PhpGenerator\Constant;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use Nette\PhpGenerator\PromotedParameter;
use Nette\PhpGenerator\Property;
use Nette\PhpGenerator\TraitUse;

use function array_map;
use function class_exists;

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
    public const FIELD_VISIBLE_PROTECTED = ClassType::VisibilityProtected;

    /**
     * Specifies class fields should be private.
     */
    public const FIELD_VISIBLE_PRIVATE = ClassType::VisibilityPrivate;

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
        } elseif ($this->updateEntityIfExists) {
            // If entity exists and we're allowed to update the entity class
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
        $className = $this->mapperInfo->className();

        $file = new PhpFile();

        $nsSeparatorPos = strrpos($className, '\\');

        $namespace = $file->addNamespace($nsSeparatorPos !== false ? substr($className, 0, $nsSeparatorPos) : '');
        $class = $namespace->addClass(substr($className, $nsSeparatorPos + 1));

        $generator = new EntityClassGenerator($class, $namespace);

        $this->generateEntityClassDeclaration($class);
        $this->generateEntityUse($generator);
        $this->generateEntityBody($generator);

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

        $namespace = null;
        $class = null;

        foreach ($file->getNamespaces() as $foundNs) {
            foreach ($foundNs->getClasses() as $foundClass) {
                if ($this->mapperInfo->className() === $foundNs->getName() . '\\' . $foundClass->getName()) {
                    $namespace = $foundNs;
                    $class = $foundClass;
                    break;
                }
            }
        }

        if (!$namespace || !$class) {
            throw new \InvalidArgumentException('The file do not contains class definition of ' . $this->mapperInfo->className());
        }

        $this->generateEntityBody(new EntityClassGenerator($class, $namespace));

        return (new ConfigurableEntityPrinter($this))
            ->printFile($file);
    }

    /**
     * Generate class inheritance and traits
     */
    protected function generateEntityClassDeclaration(ClassType $class): void
    {
        $class->addComment($class->getName());

        if ($this->classToExtend) {
            $class->setExtends($this->classToExtend);
        }

        foreach ($this->interfaces as $interface) {
            $class->addImplement($interface);
        }

        // Compatibility with nette/php-generator 3.6 and 4.1
        if (class_exists(TraitUse::class)) {
            $class->setTraits(array_map(fn ($trait) => new TraitUse($trait), $this->traits));
        } else {
            /** @psalm-suppress InvalidArgument */
            $class->setTraits($this->traits);
        }
    }

    /**
     * Generate use part
     */
    protected function generateEntityUse(EntityClassGenerator $generator): void
    {
        if (($parentClass = $this->getClassToExtend())) {
            $generator->addUse($parentClass);
        }

        foreach ($this->interfaces as $interface) {
            $generator->addUse($interface);
        }

        foreach ($this->traits as $trait) {
            $generator->addUse($trait);
        }

        foreach ($this->mapperInfo->objects() as $info) {
            $className = $info->className();
            if (!$info->belongsToRoot()) {
                continue;
            }

            $generator->addUse($className);

            if ($info->wrapper() !== null) {
                $repository = $this->prime->repository($className);
                $wrapperClass = $repository->collectionFactory()->wrapperClass($info->wrapper());

                $generator->addUse($wrapperClass);
            }
        }
    }

    protected function generateEntityBody(EntityClassGenerator $generator): void
    {
        $properties = [
            ...$this->generateEntityFieldMappingProperties($generator, $this->useConstructorPropertyPromotion),
            ...$this->generateEntityEmbeddedProperties($generator, $this->useConstructorPropertyPromotion)
        ];

        if (!$this->useConstructorPropertyPromotion) {
            foreach ($properties as $property) {
                $property->addProperty($generator);
            }
        }

        if ($this->generateEntityStubMethods) {
            $this->generateEntityStubMethods($generator);
        }

        $this->generateEntityConstructor($generator, $this->useConstructorPropertyPromotion, $properties);
    }

    /**
     * @param bool $propertyPromotion Generate constructor with property promotion
     * @param list<PropertyGenerator> $properties
     */
    protected function generateEntityConstructor(EntityClassGenerator $generator, bool $propertyPromotion, array $properties): void
    {
        $initializable = in_array(InitializableInterface::class, $this->interfaces);
        $isImportable  = in_array(ImportableInterface::class, $this->interfaces)
                    || is_subclass_of($this->classToExtend, ImportableInterface::class);

        if ($propertyPromotion) {
            $this->generateConstructorWithPromotedProperties($generator, $initializable, $properties);
        } else {
            $this->generateClassicConstructor($generator, $isImportable, $initializable, $properties);
        }

        if (!$generator->hasMethod('initialize') && $initializable) {
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
    private function generateConstructorWithPromotedProperties(EntityClassGenerator $generator, bool $initializable, array $properties): void
    {
        $isUpdate = $generator->hasMethod('__construct');
        $constructor = $isUpdate ? $generator->getMethod('__construct') : $generator->addMethod('__construct');

        foreach ($properties as $property) {
            $property->addPromotedProperty($constructor, $generator);
        }

        // Only declare new properties
        if ($isUpdate) {
            return;
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
    private function generateClassicConstructor(EntityClassGenerator $generator, bool $isImportable, bool $initializable, array $properties): void
    {
        // Do not support constructor update
        if ($generator->hasMethod('__construct')) {
            return;
        }

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

    protected function generateEntityStubMethods(EntityClassGenerator $generator): void
    {
        foreach ($this->mapperInfo->properties() as $property) {
            $this->generateSetter($generator, $property);
            $this->generateGetter($generator, $property);
        }

        foreach ($this->mapperInfo->objects() as $property) {
            if (!$property->belongsToRoot()) {
                continue;
            }

            if ($property->isArray() && $property->wrapper() === null) {
                $this->generateAdder($generator, $property);
            }

            $this->generateSetter($generator, $property);
            $this->generateGetter($generator, $property);
        }
    }

    /**
     * @param bool $forceNullable Force typehint to be nullable. Useful property promotion
     * @return list<PropertyGenerator>
     */
    protected function generateEntityFieldMappingProperties(EntityClassGenerator $class, bool $forceNullable = false): array
    {
        $properties = [];

        foreach ($this->mapperInfo->properties() as $property) {
            if ($class->hasProperty($property->name())) {
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
    protected function generateEntityEmbeddedProperties(EntityClassGenerator $class, bool $forceNullable = false): array
    {
        $properties = [];

        foreach ($this->mapperInfo->objects() as $property) {
            if (!$property->belongsToRoot() || $class->hasProperty($property->name())) {
                continue;
            }

            $properties[] = $generator = new PropertyGenerator($property->name());
            $generator->setVisibility($this->fieldVisibility);

            // Embedded property : should not be null
            if (!$property->isRelation()) {
                $generator->setNullable($forceNullable);

                // Always add a default value with use property promotion
                if ($this->useConstructorPropertyPromotion) {
                    $generator->setDefaultValue(null);
                }

                $generator->setVarTag($property->className());
                $generator->setInitialize('new '.$class->simplifyType($property->className()).'()');

                if ($this->useTypedProperties) {
                    $generator->setTypeHint($property->className());
                }

                continue;
            }

            $generator->setNullable($nullable = $forceNullable || $this->useTypedProperties);

            switch (true) {
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
                        $generator->setInitialize($class->simplifyType($property->className()).'::collection()');
                    }

                    break;

                default:
                    // Simple relation
                    $generator->setVarTag($property->className());
                    $generator->setInitialize('new '.$class->simplifyType($property->className()).'()');

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
     * @param EntityClassGenerator $generator
     * @param InfoInterface $propertyInfo
     * @param string|null $prefix Accessor prefix. Can be null to use the field name as method name.
     * @param bool $one In case of array property, get metadata for single item instead of the whole array.
     *
     * @return array{method: string, variable: string, field: string, typeHint: string, docType: string|null, nullable: bool}|null Accessor metadata, or null if the method already exists.
     */
    protected function accessorMetadata(EntityClassGenerator $generator, InfoInterface $propertyInfo, ?string $prefix, bool $one = false): ?array
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

        if ($generator->hasMethod($methodName)) {
            return null;
        }

        $variableType = null;

        if ($propertyInfo->isObject()) {
            /** @var ObjectPropertyInfo $propertyInfo */
            // Only makes nullable for single relation
            $methodTypeHint = $propertyInfo->className();
            $nullable = (!$one && !$propertyInfo->isEmbedded());
        } else {
            /** @var PropertyInfo $propertyInfo */
            $methodTypeHint = self::PROPERTY_TYPE_MAP[$propertyInfo->phpType()] ?? $propertyInfo->phpType();
            $nullable = $propertyInfo->isNullable();
        }

        if ($propertyInfo->isArray() && $one === false) {
            if ($propertyInfo->isObject() && $propertyInfo->wrapper() !== null) {
                /** @var ObjectPropertyInfo $propertyInfo */
                $repository = $this->prime->repository($propertyInfo->className());

                $methodTypeHint = $repository->collectionFactory()->wrapperClass($propertyInfo->wrapper());
                $variableType = $generator->simplifyType($propertyInfo->className()) . '[]|'.$generator->simplifyType($methodTypeHint);
            } else {
                $methodTypeHint = 'array';
                $variableType = $propertyInfo->isObject() ? $propertyInfo->className() : $propertyInfo->phpType();

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

    protected function generateGetter(EntityClassGenerator $generator, InfoInterface $propertyInfo): void
    {
        $metadata = $this->accessorMetadata($generator, $propertyInfo, $this->useGetShortcutMethod ? null : 'get');

        if (!$metadata) {
            return;
        }

        $method = $generator->addMethod($metadata['method']);
        $method->addComment('Get ' . $metadata['variable']);
        $method->addComment('');
        $method->setReturnType($metadata['typeHint']);
        $method->setReturnNullable($metadata['nullable']);
        $method->setBody('return $this->?;', [$metadata['field']]);

        if ($metadata['docType']) {
            $method->addComment('@return ' . $metadata['docType']);
        }
    }

    protected function generateSetter(EntityClassGenerator $generator, InfoInterface $propertyInfo): void
    {
        $metadata = $this->accessorMetadata($generator, $propertyInfo, 'set');

        if (!$metadata) {
            return;
        }

        $method = $generator->addMethod($metadata['method']);
        $method->addComment('Set ' . $metadata['variable']);
        $method->addComment('');

        if ($metadata['docType']) {
            $method->addComment('@param ' . $metadata['docType'] . ' $' . $metadata['variable']);
            $method->addComment('');
        }

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

    protected function generateAdder(EntityClassGenerator $generator, InfoInterface $propertyInfo): void
    {
        $metadata = $this->accessorMetadata($generator, $propertyInfo, 'add', true);

        if (!$metadata) {
            return;
        }

        $method = $generator->addMethod($metadata['method']);
        $method->addComment('Add ' . $metadata['variable']);
        $method->addComment('');

        if ($metadata['docType']) {
            $method->addComment('@param ' . $metadata['docType'] . ' $' . $metadata['variable']);
            $method->addComment('');
        }

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

    public function addProperty(EntityClassGenerator $generator): void
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

            if (!isset(EntityGenerator::PROPERTY_TYPE_MAP[$this->varTag])) {
                $type = $this->simplifyType($type, $generator);
            }

            $property->addComment("\n@var $type");
        }
    }

    public function addPromotedProperty(Method $constructor, EntityClassGenerator $generator): void
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

            if (!isset(EntityGenerator::PROPERTY_TYPE_MAP[$this->varTag])) {
                $type = $this->simplifyType($type, $generator);
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

    private function simplifyType(string $type, EntityClassGenerator $generator): string
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

            $part = $generator->simplifyType($atomicType) . ($isArray ? '[]' : '');
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

/**
 * @internal
 */
class EntityClassGenerator
{
    private ClassType $class;
    private PhpNamespace $namespace;

    /**
     * @param ClassType $class
     * @param PhpNamespace $namespace
     */
    public function __construct(ClassType $class, PhpNamespace $namespace)
    {
        $this->class = $class;
        $this->namespace = $namespace;
    }

    /**
     * Add use statement if necessary
     * Ignore classes without namespace or in current namespace
     *
     * @param string $class
     * @return void
     */
    public function addUse(string $class): void
    {
        $nsSeparatorPos = strrpos(ltrim($class, '\\'), '\\');

        // Not namespaced : do not import
        if ($nsSeparatorPos === false) {
            return;
        }

        $ns = substr($class, 0, $nsSeparatorPos);

        // Same namespace : import is not necessary
        if ($ns === $this->namespace->getName()) {
            return;
        }

        $this->namespace->addUse($class);
    }

    /**
     * Check if the given method exists on the current generated class
     * This method will check parent class and used traits
     *
     * @param string $method
     * @return bool
     */
    public function hasMethod(string $method): bool
    {
        /** @psalm-suppress InvalidArgument */
        if ($this->class->getExtends() && method_exists($this->class->getExtends(), $method)) {
            return true;
        }

        foreach ($this->class->getTraits() as $trait) {
            if ($trait instanceof TraitUse) {
                $trait = $trait->getName();
            }

            if (method_exists($trait, $method)) {
                return true;
            }
        }

        return $this->class->hasMethod($method);
    }

    /**
     * Add a new method into the class
     *
     * @param string $name Method name
     * @return Method
     */
    public function addMethod(string $name): Method
    {
        return $this->class->addMethod($name);
    }

    /**
     * Check if the given property exists on the current generated class
     * This method will check parent class and used traits
     * It will also check promoted parameters on constructor
     *
     * @param string $property
     * @return bool
     */
    public function hasProperty(string $property): bool
    {
        if ($this->class->getExtends() && property_exists($this->class->getExtends(), $property)) {
            return true;
        }

        foreach ($this->class->getTraits() as $trait) {
            if ($trait instanceof TraitUse) {
                $trait = $trait->getName();
            }

            if (property_exists($trait, $property)) {
                return true;
            }
        }

        if ($this->class->hasProperty($property)) {
            return true;
        }

        if (!$this->class->hasMethod('__construct')) {
            return false;
        }

        $parameter = $this->class->getMethod('__construct')->getParameters()[$property] ?? null;

        return $parameter instanceof PromotedParameter;
    }

    /**
     * Get a method by its name
     *
     * @param string $name
     * @return Method
     */
    public function getMethod(string $name): Method
    {
        return $this->class->getMethod($name);
    }

    /**
     * Add a new property on the entity class
     *
     * @param string $name Property name
     *
     * @return Property
     */
    public function addProperty(string $name): Property
    {
        return $this->class->addProperty($name);
    }

    /**
     * Simplify a typename if imported or in the current namespace
     *
     * @param string $type
     * @return string
     */
    public function simplifyType(string $type): string
    {
        return $this->namespace->simplifyName($type);
    }

    /**
     * @param Method|Property|Constant|TraitUse $classMember
     *
     * @return void
     */
    public function addMember($classMember): void
    {
        $this->class->addMember($classMember);
    }
}
