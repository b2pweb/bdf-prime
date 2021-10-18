<?php

namespace Bdf\Prime\Mapper\Info;

use Bdf\Prime\Relations\RelationInterface;

/**
 * ObjectPropertyInfo
 * 
 * @package Bdf\Prime\Mapper\Info
 */
class ObjectPropertyInfo implements InfoInterface
{
    /**
     * The property name
     *
     * @var string
     */
    protected $name;
    
    /**
     * The metadata from the metadata object
     *
     * @var array
     */
    protected $metadata;
    
    /**
     * The metadata from the metadata object
     *
     * @var array
     */
    protected $relation;
    
    
    /**
     * Constructor
     * 
     * @param array $metadata  The property metadata or the relation metadata
     */
    public function __construct($name, array $metadata = [])
    {
        $this->name = $name;
        $this->metadata = $metadata;
    }
    
    /**
     * Set the relation info of this field
     *
     * @param array $metadata
     *
     * @return void
     */
    public function setRelation(array $metadata): void
    {
        $this->relation = $metadata;
    }
    
    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return $this->name;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isObject()
    {
        return true;
    }
    
    /**
     * Check whether the property is a relation
     * 
     * @return bool
     */
    public function isRelation()
    {
        return $this->relation !== null;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isArray()
    {
        return $this->isRelation() && !$this->isSingleRelation($this->relation['type']);
    }

    /**
     * Get the relation wrapper collection
     *
     * @see EntityCollection
     *
     * @return string|callable|null The wrapper, or null if not set
     */
    public function wrapper()
    {
        return empty($this->relation['wrapper']) ? null : $this->relation['wrapper'];
    }

    /**
     * {@inheritdoc}
     */
    public function isEmbedded()
    {
        if ($this->isRelation() && !empty($this->relation['detached'])) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function belongsToRoot()
    {
        return $this->isEmbedded() && $this->metadata['parentPath'] === 'root';
    }

    /**
     * Get the class name of the property if it is a class
     * 
     * @return null|class-string
     */
    public function className(): ?string
    {
        if (isset($this->relation['entity'])) {
            return $this->relation['entity'];
        }
        
        if (isset($this->metadata['class'])) {
            return $this->metadata['class'];
        }
        
        return null;
    }

    /**
     * Get the foreign info from relation
     *
     * @return null|array
     */
    public function foreignInfos()
    {
        if ($this->relation['type'] === RelationInterface::BELONGS_TO) {
            return [$this->relation['entity'], $this->relation['distantKey']];
        }

        return [null, null];
    }

    /**
     * Get the local key of the relation
     *
     * @return string
     */
    public function relationKey()
    {
        return $this->relation['localKey'];
    }

    /**
     * Is the relation a one_*
     * 
     * @param string $relationType
     * 
     * @return bool
     */
    protected function isSingleRelation($relationType)
    {
        return in_array($relationType, [RelationInterface::HAS_ONE, RelationInterface::MORPH_TO, RelationInterface::BELONGS_TO]);
    }
}
