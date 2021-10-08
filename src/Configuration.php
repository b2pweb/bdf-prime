<?php

namespace Bdf\Prime;

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
}
