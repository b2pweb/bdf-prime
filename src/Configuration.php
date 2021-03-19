<?php

namespace Bdf\Prime;

use Bdf\Prime\Connection\ConnectionConfig;
use Bdf\Prime\Types\TypesRegistry;
use Bdf\Prime\Types\TypesRegistryInterface;
use Doctrine\DBAL\Configuration as BaseConfiguration;
use Psr\SimpleCache\CacheInterface;

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
            'sqlLogger'         => isset($options['logger']) ? $options['logger'] : null,
            'resultCache'       => null,
            'metadataCache'     => null,
            'dbConfig'          => null,
        ];
        
        unset($this->_attributes['logger']);
    }
    
    /**
     * Cache de resultat des requetes
     * 
     * @param \Bdf\Prime\Cache\CacheInterface $cache
     */
    public function setResultCache($cache)
    {
        $this->_attributes['resultCache'] = $cache;
    }
    
    /**
     * @return \Bdf\Prime\Cache\CacheInterface
     */
    public function getResultCache()
    {
        return $this->_attributes['resultCache'];
    }
    
    /**
     * Cache de metadata
     * 
     * @param CacheInterface $cache
     */
    public function setMetadataCache($cache)
    {
        $this->_attributes['metadataCache'] = $cache;
    }
    
    /**
     * @return CacheInterface
     */
    public function getMetadataCache()
    {
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
            $this->_attributes['dbConfig'] = $this->_attributes['dbConfig']($this);
        }

        if (! $this->_attributes['dbConfig'] instanceof ConnectionConfig) {
            $this->_attributes['dbConfig'] = new ConnectionConfig((array) $this->_attributes['dbConfig']);
        }

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
