<?php

namespace Bdf\Prime;

use Bdf\Prime\Types\TypesRegistry;
use Bdf\Prime\Types\TypesRegistryInterface;
use Doctrine\DBAL\Configuration as BaseConfiguration;

/**
 * Configuration
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
        $this->_attributes = $options + [
            'sqlLogger' => isset($options['logger']) ? $options['logger'] : null,
        ];
        
        unset($this->_attributes['logger']);
    }

    /**
     * Set common type registry
     *
     * @param TypesRegistryInterface $types
     */
    public function setTypes(TypesRegistryInterface $types)
    {
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
