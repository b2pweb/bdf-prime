<?php

namespace Bdf\Prime;

use Bdf\Prime\Platform\PlatformTypeInterface;
use Bdf\Prime\Types\TypesRegistry;
use Bdf\Prime\Types\TypesRegistryInterface;
use Doctrine\DBAL\Configuration as BaseConfiguration;

/**
 * Configuration
 *
 * @psalm-suppress InternalClass
 */
class Configuration extends BaseConfiguration
{
    /**
     * @var TypesRegistryInterface|null
     */
    private ?TypesRegistryInterface $types;

    /**
     * @var array<class-string<PlatformTypeInterface>|PlatformTypeInterface>
     */
    private array $platformTypes = [];

    /**
     * Set configuration
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if (isset($options['logger'])) {
            $this->setSQLLogger($options['logger']);

            /** @psalm-suppress InternalProperty */
            unset($options['logger']);
        }

        foreach ($options as $name => $value) {
            $this->$name = $value;
        }
    }

    /**
     * Register a custom platform type
     *
     * Note: use this with caution, override platform behavior is unlikely a good idea
     *
     * @param class-string<PlatformTypeInterface>|PlatformTypeInterface $platformType Type instance or class name
     * @param string|null $alias Type alias
     * @return void
     */
    public function addPlatformType($platformType, ?string $alias = null): void
    {
        if ($alias !== null) {
            $this->platformTypes[$alias] = $platformType;
        } else {
            $this->platformTypes[] = $platformType;
        }
    }

    /**
     * Set common type registry
     *
     * @param TypesRegistryInterface $types
     * @psalm-assert TypesRegistryInterface $this->types
     */
    public function setTypes(TypesRegistryInterface $types): void
    {
        $this->types = $types;
    }

    /**
     * Get common types registry
     *
     * @return TypesRegistryInterface
     */
    public function getTypes(): TypesRegistryInterface
    {
        if (!isset($this->types)) {
            $this->setTypes(new TypesRegistry());
        }

        return $this->types;
    }

    /**
     * Get custom platform types
     *
     * @return array<class-string<PlatformTypeInterface>|PlatformTypeInterface>
     */
    public function getPlatformTypes(): array
    {
        return $this->platformTypes;
    }
}
