<?php

namespace Bdf\Prime\Platform;

use Bdf\Prime\Exception\TypeException;
use Bdf\Prime\Types\FacadeTypeInterface;
use Bdf\Prime\Types\TypeInterface;
use Bdf\Prime\Types\TypesRegistry;
use Bdf\Prime\Types\TypesRegistryInterface;

/**
 * Manage types of platform
 */
class PlatformTypes extends TypesRegistry implements PlatformTypesInterface
{
    /**
     * Map of interface to prime type
     *
     * @var string[]
     *
     * @internal resolve use this for its optimisation
     */
    private $interfaceTypes = [
        \DateTimeInterface::class => TypeInterface::DATETIME,
    ];

    /**
     * Map of class to prime type
     *
     * @var string[]
     *
     * @internal resolve use this for its optimisation
     */
    private $classTypes = [
        \DateTime::class          => TypeInterface::DATETIME,
        \DateTimeImmutable::class => TypeInterface::DATETIME,
        \stdClass::class          => TypeInterface::OBJECT,
    ];

    /**
     * @var PlatformInterface
     */
    private $platform;

    /**
     * @var TypesRegistryInterface
     */
    private $commons;


    /**
     * PlatformTypes constructor.
     *
     * @param PlatformInterface $platform
     * @param PlatformTypeInterface[]|string[] $nativeTypes
     * @param TypesRegistryInterface $commons
     */
    public function __construct(PlatformInterface $platform, array $nativeTypes, TypesRegistryInterface $commons)
    {
        parent::__construct($nativeTypes);

        $this->platform = $platform;
        $this->commons  = $commons;
    }

    /**
     * {@inheritdoc}
     *
     * @todo Optimize
     */
    public function isNative(string $name): bool
    {
        return parent::has($name) && parent::get($name) instanceof PlatformTypeInterface;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name): TypeInterface
    {
        if (parent::has($name)) {
            return parent::get($name);
        }

        return $this->commons->get($name);
    }

    /**
     * {@inheritdoc}
     */
    public function native(string $name): PlatformTypeInterface
    {
        $type = $this->get($name);

        if ($type instanceof PlatformTypeInterface) {
            return $type;
        }

        /** @var FacadeTypeInterface $type */
        return $type->toPlatformType($this->platform);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $type): bool
    {
        return parent::has($type) || $this->commons->has($type);
    }

    /**
     * {@inheritdoc}
     *
     * @todo revoir la gestion. Doit on appeler une methode TypeInterface::support()
     */
    public function resolve($value): ?TypeInterface
    {
        $type = gettype($value);

        if ($type === 'object') {
            $className = get_class($value);

            if (isset($this->classTypes[$className])) {
                return $this->get($this->classTypes[$className]);
            }

            foreach ($this->interfaceTypes as $class => $mappedType) {
                if ($value instanceof $class) {
                    return $this->get($mappedType);
                }
            }
        } elseif ($type === 'array' && is_string(key($value))) {
            return $this->get(TypeInterface::ARRAY_OBJECT);
        } elseif ($value === null) {
            return $this->get(TypeInterface::STRING);
        }

        return $this->get($type);
    }

    /**
     * {@inheritdoc}
     */
    public function toDatabase($value, $type = null)
    {
        //ORM optimisation: type is most of the type provides
        if ($type instanceof TypeInterface) {
            return $type->toDatabase($value);
        }

        if ($type === null) {
            return $this->resolve($value)->toDatabase($value);
        }

        if (is_string($type)) {
            return $this->get($type)->toDatabase($value);
        }

        throw new TypeException(gettype($value), 'Cannot convert to database the value : ' . print_r($value, true).PHP_EOL.'You should set a valid type as second parameter');
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value, $type = null, array $fieldOptions = [])
    {
        //ORM optimisation: type is most of the type provides
        if ($type instanceof TypeInterface) {
            return $type->fromDatabase($value, $fieldOptions);
        }

        if (is_string($type)) {
            return $this->get($type)->fromDatabase($value, $fieldOptions);
        }

        // We supposed that type is null. If value is also null we return the php value 'null'
        if ($value === null) {
            return null;
        }

        throw new TypeException(gettype($value), 'Cannot convert to php the value : ' . print_r($value, true).PHP_EOL.'You should set a valid type as second parameter');
    }

    /**
     * Instantiate the platform type object
     *
     * @param class-string<TypeInterface> $class
     * @param string $name
     *
     * @return PlatformTypeInterface
     */
    protected function instantiate($class, $name)
    {
        return new $class($this->platform, $name);
    }
}
