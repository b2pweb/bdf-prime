<?php

namespace Bdf\Prime;

use Bdf\Prime\Connection\ConnectionConfig;
use Bdf\Prime\Types\TypesRegistry;
use Bdf\Prime\Types\TypesRegistryInterface;
use Doctrine\DBAL\Configuration as BaseConfiguration;
use Psr\SimpleCache\CacheInterface;

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
        $this->_attributes = $options + [
            'sqlLogger'         => isset($options['logger']) ? $options['logger'] : null,
            'resultCache'       => null,
            'metadataCache'     => null,
            'dbConfig'          => null,
        ];

        /** @psalm-suppress InternalProperty */
        unset($this->_attributes['logger']);
    }
    
    /**
     * Cache de resultat des requetes
     * 
     * @param \Bdf\Prime\Cache\CacheInterface $cache
     */
    public function setResultCache($cache)
    {
        /** @psalm-suppress InternalProperty */
        $this->_attributes['resultCache'] = $cache;
    }
    
    /**
     * @return \Bdf\Prime\Cache\CacheInterface
     */
    public function getResultCache()
    {
        /** @psalm-suppress InternalProperty */
        return $this->_attributes['resultCache'];
    }
    
    /**
     * Cache de metadata
     * 
     * @param CacheInterface $cache
     */
    public function setMetadataCache($cache)
    {
        /** @psalm-suppress InternalProperty */
        $this->_attributes['metadataCache'] = $cache;
    }
    
    /**
     * @return CacheInterface
     */
    public function getMetadataCache()
    {
        /** @psalm-suppress InternalProperty */
        return $this->_attributes['metadataCache'];
    }
    
    /**
     * Set db config.
     * 
     * Contains profil info to connect database
     * 
     * @param callable|ConnectionConfig|array $config   Config object or config file
     *
     * @deprecated Since 1.1. Use ConnectionRegistry to declare your connections.
     */
    public function setDbConfig($config)
    {
        @trigger_error(__METHOD__.' is deprecated since 1.1 and will be removed in 1.2. Use ConnectionRegistry to declare your connections.', E_USER_DEPRECATED);

        /** @psalm-suppress InternalProperty */
        $this->_attributes['dbConfig'] = $config;
    }
    
    /**
     * Get the db config object
     * 
     * @return ConnectionConfig
     *
     * @deprecated Since 1.1. Use ConnectionRegistry to declare your connections.
     */
    public function getDbConfig()
    {
        @trigger_error(__METHOD__.' is deprecated since 1.1 and will be removed in 1.2. Use ConnectionRegistry to declare your connections.', E_USER_DEPRECATED);

        if ($this->_attributes['dbConfig'] instanceof ConnectionConfig) {
            return $this->_attributes['dbConfig'];
        }

        if (is_callable($this->_attributes['dbConfig'])) {
            /** @psalm-suppress InternalProperty */
            $this->_attributes['dbConfig'] = $this->_attributes['dbConfig']($this);
        }

        if (! $this->_attributes['dbConfig'] instanceof ConnectionConfig) {
            /** @psalm-suppress InternalProperty */
            $this->_attributes['dbConfig'] = new ConnectionConfig((array) $this->_attributes['dbConfig']);
        }

        /** @psalm-suppress InternalProperty */
        return $this->_attributes['dbConfig'];
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
