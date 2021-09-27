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
     * Set configuration
     * 
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        /** @psalm-suppress InternalProperty */
        $this->_attributes = $options;

        if (isset($options['logger'])) {
            $this->_attributes['sqlLogger'] = $options['logger'];

            /** @psalm-suppress InternalProperty */
            unset($this->_attributes['logger']);
        }
    }

    /**
     * Set common type registry
     *
     * @param TypesRegistryInterface $types
     * @psalm-assert TypesRegistryInterface $this->_attributes['types']
     */
    public function setTypes(TypesRegistryInterface $types)
    {
        /** @psalm-suppress InternalProperty */
        $this->_attributes['types'] = $types;
    }

    /**
     * Get common types registry
     *
     * @return TypesRegistryInterface
     */
    public function getTypes()
    {
        if (!isset($this->_attributes['types'])) {
            $this->setTypes(new TypesRegistry());
        }

        return $this->_attributes['types'];
    }
}
