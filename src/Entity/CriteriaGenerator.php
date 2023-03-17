<?php

namespace Bdf\Prime\Entity;

use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Mapper\Metadata;
use LogicException;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;

use function file_get_contents;

/**
 * Generate a criteria class for a given entity
 */
final class CriteriaGenerator
{
    private string $className;
    private ?PhpFile $file = null;
    private ?ClassType $class = null;

    /**
     * @param string $className The generated criteria class name. Can be a FQCN (i.e. with namespace)
     */
    public function __construct(string $className)
    {
        $this->className = $className;
    }

    /**
     * Loads a criteria class from a file
     * Calling this method permit to update the class (i.e. keep the existing methods) instead of regenerating it.
     *
     * @param string $filename The file path to load
     * @return void
     */
    public function loadFromFile(string $filename): void
    {
        $this->file = PhpFile::fromCode(file_get_contents($filename));
        $this->class = $this->file->getClasses()[$this->className] ?? null;
    }

    /**
     * Declare a setter method for each attribute of the entity
     *
     * @param Metadata $metadata
     * @return void
     */
    public function parseMetadata(Metadata $metadata): void
    {
        $class = $this->class();

        foreach ($metadata->attributes() as $field) {
            if (isset($field['embedded']) || $class->hasMethod($field['attribute'])) {
                continue;
            }

            $method = $class->addMethod($field['attribute']);

            $method->addParameter('value');
            $method->setReturnType('self');

            $method->addBody('$this->add(?, $value);', [$field['attribute']]);
            $method->addBody('return $this;');
        }
    }

    /**
     * Declare a setter for each custom filter
     *
     * @param array<string, callable> $customFilters
     * @return void
     *
     * @see Mapper::filters()
     */
    public function parseCustomFilters(array $customFilters): void
    {
        $class = $this->class();

        foreach ($customFilters as $name => $filter) {
            if ($class->hasMethod($name)) {
                continue;
            }

            $method = $class->addMethod($name);
            $method->setReturnType('self');

            $reflection = is_array($filter) ? new ReflectionMethod($filter[0], $filter[1]) : new ReflectionFunction($filter);

            if (!$parameterReflection = $reflection->getParameters()[1] ?? null) {
                continue;
            }

            $method->addParameter($parameterReflection->getName())
                ->setType($parameterReflection->getType() instanceof ReflectionNamedType ? $parameterReflection->getType()->getName() : null)
            ;

            $method->addBody('$this->add(?, $?);', [$name, $parameterReflection->getName()]);
            $method->addBody('return $this;');
        }
    }

    /**
     * Dump the generated class
     * Note: the code starts with an open `<?php` tag
     *
     * @return string
     */
    public function dump(): string
    {
        if (!$this->file) {
            throw new LogicException('No criteria class generated. Call parseMetadata() or parseCustomFilters() first');
        }

        return (new PsrPrinter())->printFile($this->file);
    }

    private function class(): ClassType
    {
        if ($this->class) {
            return $this->class;
        }

        if (!$this->file) {
            $this->file = new PhpFile();
        }

        $this->class = $this->file->addClass($this->className);
        $this->class->setExtends(Criteria::class);

        foreach ($this->file->getNamespaces() as $namespace) {
            $namespace->addUse(Criteria::class);
        }

        return $this->class;
    }
}
