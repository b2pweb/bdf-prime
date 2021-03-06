<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Bdf\Prime\Entity;

use Bdf\Prime\Mapper\Info\InfoInterface;
use Bdf\Prime\Mapper\Info\ObjectPropertyInfo;
use Bdf\Prime\Mapper\Info\PropertyInfo;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Mapper\Info\MapperInfo;
use Bdf\Prime\ServiceLocator;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\Inflector\Inflector as InflectorObject;
use Doctrine\Inflector\InflectorFactory;

/**
 * Generic class used to generate PHP5 entity classes from Mapper.
 *
 *     [php]
 *     $mapper = $service->mappers()->build('Entity);
 *
 *     $generator = new EntityGenerator();
 *     $generator->setGenerateStubMethods(true);
 *     $generator->setRegenerateEntityIfExists(false);
 *     $generator->setUpdateEntityIfExists(true);
 *     $generator->generate($mapper, '/path/to/generate/entities');
 *
 *
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class EntityGenerator
{
    /**
     * Specifies class fields should be protected.
     */
    const FIELD_VISIBLE_PROTECTED = 'protected';

    /**
     * Specifies class fields should be private.
     */
    const FIELD_VISIBLE_PRIVATE = 'private';

    /**
     * The prime service locator
     *
     * @var ServiceLocator
     */
    private $prime;

    /**
     * The inflector instance
     *
     * @var InflectorObject
     */
    private $inflector;

    /**
     * The mapper info
     *
     * @var MapperInfo
     */
    private $mapperInfo;

    /**
     * The extension to use for written php files.
     *
     * @var string
     */
    private $extension = '.php';

    /**
     * Whether or not the current Mapper instance is new or old.
     *
     * @var boolean
     */
    private $isNew = true;

    /**
     * @var array
     */
    private $staticReflection = [];

    /**
     * Number of spaces to use for indention in generated code.
     */
    private $numSpaces = 4;

    /**
     * The actual spaces to use for indention.
     *
     * @var string
     */
    private $spaces = '    ';

    /**
     * The class all generated entities should extend.
     *
     * @var string
     */
    private $classToExtend;

    /**
     * The interfaces all generated entities should implement.
     *
     * @var array
     */
    private $interfaces = [];

    /**
     * The traits
     *
     * @var array
     */
    private $traits = [];

    /**
     * Whether or not to generate sub methods.
     *
     * @var boolean
     */
    private $generateEntityStubMethods = true;

    /**
     * Whether or not to update the entity class if it exists already.
     *
     * @var boolean
     */
    private $updateEntityIfExists = false;

    /**
     * Whether or not to re-generate entity class if it exists already.
     *
     * @var boolean
     */
    private $regenerateEntityIfExists = false;

    /**
     * The name of get methods will not contains the 'get' prefix
     *
     * @var boolean
     */
    private $useGetShortcutMethod = true;

    /**
     * Visibility of the field
     *
     * @var string
     */
    private $fieldVisibility = self::FIELD_VISIBLE_PROTECTED;

    /**
     * @var string
     */
    private static $classTemplate =
'<?php

<namespace><useStatement><entityAnnotation>
<entityClassName>
{
<entityTraits><entityBody>
}
';

    /**
     * @var string
     */
    private static $getMethodTemplate =
'/**
 * <description>
 *
 * @return <variableType>
 */
public function <methodName>()
{
<spaces>return $this-><fieldName>;
}
';

    /**
     * @var string
     */
    private static $setMethodTemplate =
'/**
 * <description>
 *
 * @param <variableType> $<variableName>
 *
 * @return $this
 */
public function <methodName>(<methodTypeHint>$<variableName><variableDefault>)
{
<spaces>$this-><fieldName> = $<variableName>;

<spaces>return $this;
}
';

    /**
     * @var string
     */
    private static $addMethodTemplate =
'/**
 * <description>
 *
 * @param <variableType> $<variableName>
 *
 * @return $this
 */
public function <methodName>(<methodTypeHint>$<variableName>)
{
<spaces>$this-><fieldName>[] = $<variableName>;

<spaces>return $this;
}
';

    /**
     * @var string
     */
    private static $methodTemplate =
'/**
 * <description>
 */
public function <methodName>()
{
<spaces><content>
}
';

    /**
     * @var string
     */
    private static $constructorMethodTemplate =
'/**
 * Constructor
 */
public function __construct()
{
<spaces><collections>
}
';

    /**
     * @var string
     */
    private static $importableConstructorMethodTemplate =
'/**
 * Constructor
 *
 * @param array $data
 */
public function __construct(array $data = [])
{
<spaces><initialize>$this->import($data);
}
';

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
     * @param string $file    Entity file name
     *
     * @return string|false If no generation
     * 
     * @api
     */
    public function generate($mapper, $file = null)
    {
        $this->isNew = !$file || !file_exists($file) || $this->regenerateEntityIfExists;

        // If entity doesn't exist or we're re-generating the entities entirely
        if ($this->isNew || !$file) {
            return $this->generateEntityClass($mapper);
        // If entity exists and we're allowed to update the entity class
        } elseif ($this->updateEntityIfExists && $file) {
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
    public function generateEntityClass(Mapper $mapper)
    {
        $this->mapperInfo = $mapper->info();
        
        $this->staticReflection[$this->mapperInfo->className()] = ['properties' => [], 'methods' => []];
        
        $placeHolders = array(
            '<namespace>',
            '<useStatement>',
            '<entityAnnotation>',
            '<entityClassName>',
            '<entityTraits>',
            '<entityBody>'
        );

        $replacements = array(
            $this->generateEntityNamespace(),
            $this->generateEntityUse(),
            $this->generateEntityDocBlock(),
            $this->generateEntityClassName(),
            $this->generateEntityTraits(),
            $this->generateEntityBody()
        );

        $code = str_replace($placeHolders, $replacements, static::$classTemplate);

        return str_replace('<spaces>', $this->spaces, $code);
    }

    /**
     * Generates the updated code for the given Mapper and entity at path.
     *
     * @param Mapper $mapper
     * @param string $file
     *
     * @return string
     */
    public function generateUpdatedEntityClass(Mapper $mapper, $file)
    {
        $this->mapperInfo = $mapper->info();
        
        $currentCode = file_get_contents($file);

        $this->parseTokensInEntityFile($currentCode);
        
        $body = $this->generateEntityBody();
        $body = str_replace('<spaces>', $this->spaces, $body);
        $last = strrpos($currentCode, '}');

        return substr($currentCode, 0, $last) . $body . (strlen($body) > 0 ? "\n" : '') . "}\n";
    }

    /**
     * @return string
     */
    protected function generateEntityNamespace()
    {
        if ($this->hasNamespace($this->mapperInfo->className())) {
            return 'namespace ' . $this->getNamespace($this->mapperInfo->className()) .';' . "\n\n";
        }
    }

    /**
     * Generate use part
     * 
     * @return string
     */
    protected function generateEntityUse()
    {
        $use = [];
        
        if ($this->hasNamespace($this->getClassToExtend())) {
            $use[$this->getClassToExtend()] = 'use ' . $this->getClassToExtend() . ';';
        }
        
        foreach ($this->interfaces as $interface) {
            if ($this->hasNamespace($interface)) {
                $use[$interface] = 'use ' . $interface . ';';
            }
        }
        
        foreach ($this->traits as $trait) {
            if ($this->hasNamespace($trait)) {
                $use[$trait] = 'use ' . $trait . ';';
            }
        }
        
        foreach ($this->mapperInfo->objects() as $info) {
            $className = $info->className();
            if (!$info->belongsToRoot()) {
                continue;
            }

            if ($this->hasNamespace($className)) {
                $use[$className] = 'use '.$className.';';
            }

            if ($info->wrapper() !== null) {
                $repository = $this->prime->repository($className);
                $wrapperClass = $repository->collectionFactory()->wrapperClass($info->wrapper());

                if ($this->hasNamespace($wrapperClass)) {
                    $use[$wrapperClass] = 'use '.$wrapperClass.';';
                }
            }
        }
        
        if (!$use) {
            return '';
        }
        
        sort($use);
        
        return implode("\n", $use) . "\n\n";
    }
    
    /**
     * @return string
     */
    protected function generateEntityClassName()
    {
        return 'class ' . $this->getClassName($this->mapperInfo->className()) .
            ($this->classToExtend ? ' extends ' . $this->getClassToExtendName() : null) .
            ($this->interfaces ? ' implements ' . $this->getInterfacesToImplement() : null);
    }

    /**
     * @return string
     */
    protected function generateEntityTraits()
    {
        if (!$this->traits) {
            return '';
        }
        
        $traits = '';
        
        foreach ($this->traits as $trait) {
            $traits .= $this->spaces . 'use ' . $this->getRelativeClassName($trait) . ';' . "\n";
        }
        
        return $traits . "\n";
    }

    /**
     * @param Mapper $mapper
     *
     * @return string
     */
    protected function generateEntityBody()
    {
        $fieldMappingProperties = $this->generateEntityFieldMappingProperties();
        $embeddedProperties = $this->generateEntityEmbeddedProperties();
        $stubMethods = $this->generateEntityStubMethods ? $this->generateEntityStubMethods() : null;

        $code = array();

        if ($fieldMappingProperties) {
            $code[] = $fieldMappingProperties;
        }

        if ($embeddedProperties) {
            $code[] = $embeddedProperties;
        }

        $code[] = $this->generateEntityConstructor();

        if ($stubMethods) {
            $code[] = $stubMethods;
        }

        return implode("\n", $code);
    }

    /**
     * @return string
     */
    protected function generateEntityConstructor()
    {
        $initializable = in_array(InitializableInterface::class, $this->interfaces);
        $isImportable  = in_array(ImportableInterface::class, $this->interfaces)
                    || is_subclass_of($this->classToExtend, ImportableInterface::class);

        $collections = [];

        foreach ($this->mapperInfo->objects() as $property) {
            if (!$property->belongsToRoot()) {
                continue;
            }

            if ($property->isRelation()) {
                if (!$property->isArray()) {
                    $collections[$property->name()] = '$this->'.$property->name().' = new '.$this->getRelativeClassName($property->className()).'();';
                } elseif ($property->wrapper() === 'collection') { // @todo handle other wrapper types
                    $collections[$property->name()] = '$this->'.$property->name().' = '.$this->getRelativeClassName($property->className()).'::collection();';
                }
            } else {
                $collections[$property->name()] = '$this->'.$property->name().' = new '.$this->getRelativeClassName($property->className()).'();';
            }
        }
        foreach ($this->mapperInfo->properties() as $property) {
            if ($property->isDateTime() && $property->hasDefault()) {
                $constructorArgs = '';
                // Add the default timezone from the property type.
                if ($timezone = $property->getTimezone()) {
                    $constructorArgs = "'now', new \DateTimeZone('$timezone')";
                }

                $collections[$property->name()] = '$this->'.$property->name().' = new '.$property->phpType().'('.$constructorArgs.');';
            }
        }
        
        $methods = [];
        
        if (!$this->hasMethod('__construct')) {
            if ($isImportable) {
                $buffer = '';

                if ($initializable) {
                    $buffer = '$this->initialize();'."\n".$this->spaces;
                } elseif ($collections) {
                    $buffer = implode("\n".$this->spaces, $collections)."\n".$this->spaces;
                }

                $methods[] = $this->prefixCodeWithSpaces(str_replace("<initialize>", $buffer, static::$importableConstructorMethodTemplate));
            } elseif ($collections && !$initializable) {
                $methods[] = $this->prefixCodeWithSpaces(str_replace("<collections>", implode("\n".$this->spaces, $collections), static::$constructorMethodTemplate));
            }
        }
        
        if (!$this->hasMethod('initialize') && $initializable) {
            $methods[] = $this->generateMethod('{@inheritdoc}', 'initialize', implode("\n".$this->spaces, $collections));
        }

        return implode("\n", $methods);
    }

    /**
     * @param Mapper $mapper
     *
     * @return string
     */
    protected function generateEntityDocBlock()
    {
        $lines = array();
        $lines[] = '/**';
        $lines[] = ' * ' . $this->getClassName($this->mapperInfo->className());
        $lines[] = ' */';
        
        return implode("\n", $lines);
    }
    
    /**
     * @return string
     */
    protected function generateEntityStubMethods()
    {
        $methods = [];
        
        foreach ($this->mapperInfo->properties() as $property) {
            if ($code = $this->generateEntityStubMethod('set', $property)) {
                $methods[] = $code;
            }

            if ($code = $this->generateEntityStubMethod('get', $property)) {
                $methods[] = $code;
            }
        }

        foreach ($this->mapperInfo->objects() as $property) {
            if (!$property->belongsToRoot()) {
                continue;
            }

            if (!$property->isArray() || $property->wrapper() !== null) {
                if ($code = $this->generateEntityStubMethod('set', $property)) {
                    $methods[] = $code;
                }
                if ($code = $this->generateEntityStubMethod('get', $property)) {
                    $methods[] = $code;
                }
            } else {
                if ($code = $this->generateEntityStubMethod('add', $property)) {
                    $methods[] = $code;
                }
                if ($code = $this->generateEntityStubMethod('set', $property)) {
                    $methods[] = $code;
                }
                if ($code = $this->generateEntityStubMethod('get', $property)) {
                    $methods[] = $code;
                }
            }
        }

        return implode("\n", $methods);
    }

    /**
     * @return string
     */
    protected function generateEntityFieldMappingProperties()
    {
        $lines = array();

        foreach ($this->mapperInfo->properties() as  $property) {
            if ($this->hasProperty($property->name())) {
                continue;
            }
            
            $default = '';
            if ($property->hasDefault() && !$property->isDateTime()) {
                $default = ' = '.$this->stringfyValue(
                    $property->convert($property->getDefault())
                );
            } elseif ($property->isArray()) {
                $default = ' = []';
            }

            $lines[] = $this->generateFieldMappingPropertyDocBlock($property);
            $lines[] = $this->spaces.$this->fieldVisibility.' $'.$property->name().$default.";\n";
        }

        return implode("\n", $lines);
    }

    /**
     * @return string
     */
    protected function generateEntityEmbeddedProperties()
    {
        $lines = array();

        foreach ($this->mapperInfo->objects() as $property) {
            if (!$property->belongsToRoot() || $this->hasProperty($property->name())) {
                continue;
            }
            
            if (!$property->isRelation()) {
                $lines[] = $this->generateEmbeddedPropertyDocBlock($property);
                $lines[] = $this->spaces.$this->fieldVisibility.' $'.$property->name().";\n";
            } else {
                $name = $property->name();

                // Do not initialize the property if it's a wrapper
                if ($property->isArray() && $property->wrapper() === null) {
                    $name .= ' = []';
                }

                $lines[] = $this->generateEmbeddedPropertyDocBlock($property);
                $lines[] = $this->spaces.$this->fieldVisibility.' $'.$name.";\n";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param string            $type
     * @param InfoInterface     $propertyInfo
     * @param string|null       $defaultValue
     *
     * @return string
     */
    protected function generateEntityStubMethod($type, InfoInterface $propertyInfo, $defaultValue = null)
    {
        $fieldName = $propertyInfo->name();

        // The hint flag help algorythm to determine the hint info for object parameter.
        // It should be 'array' for collection but the add method need the object hint.
        // setItems(array $items)
        // addItem(Item $item)
        $hintOne = false;

        if ($type === 'get' && $this->useGetShortcutMethod === true) {
            $variableName = $this->inflector->camelize($fieldName);
            $methodName = $variableName;
        } else {
            $methodName = $type . $this->inflector->classify($fieldName);
            $variableName = $this->inflector->camelize($fieldName);
        }
        
        if ($type === 'add') {
            $methodName = $this->inflector->singularize($methodName);
            $variableName = $this->inflector->singularize($variableName);
            $hintOne = true;
        }

        if ($this->hasMethod($methodName)) {
            return '';
        }
        $this->staticReflection[$this->mapperInfo->className()]['methods'][] = strtolower($methodName);

        if ($propertyInfo->isObject()) {
            $variableType = $this->getRelativeClassName($propertyInfo->className());
            $methodTypeHint =  $variableType.' ';
        } else {
            $variableType = $propertyInfo->phpType();
            $methodTypeHint = null;
        }

        if ($propertyInfo->isArray() && $hintOne === false) {
            if ($propertyInfo->isObject() && $propertyInfo->wrapper() !== null) {
                $repository = $this->prime->repository($propertyInfo->className());
                $wrapperClass = $this->getRelativeClassName($repository->collectionFactory()->wrapperClass($propertyInfo->wrapper()));

                $methodTypeHint = $wrapperClass.' ';
                $variableType .= '[]|'.$wrapperClass;
            } else {
                $methodTypeHint = 'array ';

                if ($variableType !== 'array') {
                    $variableType .= '[]';
                }
            }
        }

        $replacements = array(
          '<description>'       => ucfirst($type).' '.$variableName,
          '<methodTypeHint>'    => $methodTypeHint,
          '<variableType>'      => $variableType,
          '<variableName>'      => $variableName,
          '<methodName>'        => $methodName,
          '<fieldName>'         => $fieldName,
          '<variableDefault>'   => ($defaultValue !== null ) ? (' = '.$defaultValue) : ''
        );

        $method = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $this->getMethodTemplate($type)
        );

        return $this->prefixCodeWithSpaces($method);
    }

    /**
     * Get the template of the method
     *
     * @param string $prefix
     *
     * @return string
     *
     * @throws \LogicException
     */
    private function getMethodTemplate($prefix)
    {
        switch ($prefix) {
            case 'get':
                return static::$getMethodTemplate;

            case 'add':
                return static::$addMethodTemplate;

            case 'set':
                return static::$setMethodTemplate;
        }

        throw new \LogicException('No template found for method "'.$prefix.'"');
    }

    /**
     * @param string $description
     * @param string $methodName
     * @param string $content
     *
     * @return string
     */
    protected function generateMethod($description, $methodName, $content = null)
    {
        if ($this->hasMethod($methodName)) {
            return '';
        }
        
        $this->staticReflection[$this->mapperInfo->className()]['methods'][] = $methodName;

        $replacements = array(
            '<description>' => $description,
            '<methodName>'  => $methodName,
            '<content>'     => $content,
        );

        $method = str_replace(
            array_keys($replacements),
            array_values($replacements),
            static::$methodTemplate
        );

        return $this->prefixCodeWithSpaces($method);
    }

    /**
     * @param PropertyInfo $property
     *
     * @return string
     */
    protected function generateFieldMappingPropertyDocBlock($property)
    {
        $lines = array();
        $lines[] = $this->spaces . '/**';
        $lines[] = $this->spaces . ' * @var '.$property->phpType();
        $lines[] = $this->spaces . ' */';

        return implode("\n", $lines);
    }

    /**
     * @param ObjectPropertyInfo $property
     *
     * @return string
     */
    protected function generateEmbeddedPropertyDocBlock($property)
    {
        $className = $property->className();
        if ($className) {
            $className = $this->getRelativeClassName($className);

            if ($property->isArray()) {
                if ($property->wrapper() !== null) {
                    $repository = $this->prime->repository($property->className());
                    $className = $this->getRelativeClassName($repository->collectionFactory()->wrapperClass($property->wrapper())).'|'.$className.'[]';
                } else {
                    $className .= '[]';
                }
            }
        } else {
            $className = '{type}';
        }
        
        $lines = array();
        $lines[] = $this->spaces . '/**';
        $lines[] = $this->spaces . ' * @var '.$className;
        $lines[] = $this->spaces . ' */';

        return implode("\n", $lines);
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
    protected function parseTokensInEntityFile($src)
    {
        $tokens = token_get_all($src);
        $lastSeenNamespace = "";
        $lastSeenClass = false;

        $inNamespace = false;
        $inClass = false;

        for ($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            if (in_array($token[0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT))) {
                continue;
            }

            if ($inNamespace) {
                if ($token[0] == T_NS_SEPARATOR || $token[0] == T_STRING) {
                    $lastSeenNamespace .= $token[1];
                } elseif (is_string($token) && in_array($token, array(';', '{'))) {
                    $inNamespace = false;
                }
            }

            if ($inClass) {
                $inClass = false;
                $lastSeenClass = $lastSeenNamespace . ($lastSeenNamespace ? '\\' : '') . $token[1];
                $this->staticReflection[$lastSeenClass]['properties'] = array();
                $this->staticReflection[$lastSeenClass]['methods'] = array();
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
            } elseif (in_array($token[0], array(T_VAR, T_PUBLIC, T_PRIVATE, T_PROTECTED)) && $tokens[$i+2][0] != T_FUNCTION) {
                $this->staticReflection[$lastSeenClass]['properties'][] = substr($tokens[$i+2][1], 1);
            }
        }
    }

    /**
     * @param string $property
     *
     * @return bool
     */
    protected function hasProperty($property)
    {
        if ($this->classToExtend || (!$this->isNew && class_exists($this->mapperInfo->className()))) {
            // don't generate property if its already on the base class.
            $reflClass = new \ReflectionClass($this->getClassToExtend() ?: $this->mapperInfo->className());
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
    protected function hasMethod($method)
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
     * Get class name relative to use
     * 
     * @param string $className
     * @return string
     */
    protected function getRelativeClassName($className)
    {
        $className = ltrim($className, '\\');
        
        if ($this->hasNamespace($className)) {
            return $this->getClassName($className);
        } else {
            return '\\' . $className;
        }
    }
    
    /**
     * Get the class short name
     * 
     * @param string $className
     *
     * @return string
     */
    protected function getClassName($className)
    {
        $parts = explode('\\', $className);
        return array_pop($parts);
    }

    /**
     * @param string $className
     *
     * @return string
     */
    protected function getNamespace($className)
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
    protected function hasNamespace($className)
    {
        return strrpos($className, '\\') != 0;
    }

    /**
     * @return string
     */
    protected function getClassToExtendName()
    {
        $refl = new \ReflectionClass($this->getClassToExtend());

        return $this->getRelativeClassName($refl->getName());
    }

    /**
     * @return string
     */
    protected function getInterfacesToImplement()
    {
        $interfaces = [];
        
        foreach ($this->interfaces as $interface) {
            $refl = new \ReflectionClass($interface);

            $interfaces[] = $this->getRelativeClassName($refl->getName());
        }
        
        return implode(', ', $interfaces);
    }
    
    /**
     * @param Mapper $mapper
     *
     * @return \ReflectionClass[]
     */
    protected function getTraitsReflections()
    {
        if ($this->isNew) {
            return [];
        }
        
        $reflClass = new \ReflectionClass($this->mapperInfo->className());

        $traits = array();

        while ($reflClass !== false) {
            $traits = array_merge($traits, $reflClass->getTraits());

            $reflClass = $reflClass->getParentClass();
        }

        return $traits;
    }
    
    /**
     * @param string $code
     * @param int    $num
     *
     * @return string
     */
    protected function prefixCodeWithSpaces($code, $num = 1)
    {
        $lines = explode("\n", $code);

        foreach ($lines as $key => $value) {
            if ( ! empty($value)) {
                $lines[$key] = str_repeat($this->spaces, $num) . $lines[$key];
            }
        }

        return implode("\n", $lines);
    }
    
    /**
     * Get string representation of a value
     * 
     * @param mixed $value
     *
     * @return string
     */
    protected function stringfyValue($value)
    {
        if (is_array($value)) {
            if (empty($value)) {
                return '[]';
            }
            
            return var_export($value, true);
        }
        
        if (null === $value) {
            return 'null';
        }
        
        if (is_string($value)) {
            return "'" . $value . "'";
        }
        
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return $value;
    }

    //---------------------- mutators

    /**
     * Sets the number of spaces the exported class should have.
     *
     * @api
     */
    public function setNumSpaces(int $numSpaces): void
    {
        $this->spaces = str_repeat(' ', $numSpaces);
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
     */
    public function addInterface(string $interface)
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
     */
    public function addTrait(string $trait)
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
     * @param bool $bool
     *
     * @return void
     *
     * @api
     */
    public function useGetShortcutMethod($bool = true)
    {
        $this->useGetShortcutMethod = $bool;
    }

    /**
     * Get the flag for get mehtod name.
     */
    public function getUseGetShortcutMethod(): bool
    {
        return $this->useGetShortcutMethod;
    }
}