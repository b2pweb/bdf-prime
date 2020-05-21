<?php

namespace Bdf\Prime\Entity\Hydrator\Generator;

/**
 * Information about an embedded object
 */
class EmbeddedInfo
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var array
     */
    private $metadata;

    /**
     * @var AttributesResolver
     */
    private $resolver;


    /**
     * EmbeddedInfo constructor.
     *
     * @param string $path
     * @param array $metadata
     * @param AttributesResolver $resolver
     */
    public function __construct($path, array $metadata, AttributesResolver $resolver)
    {
        $this->path = $path;
        $this->metadata = $metadata;
        $this->resolver = $resolver;
    }

    /**
     * @return string
     */
    public function path()
    {
        return $this->metadata['path'];
    }

    /**
     * @return array
     */
    public function paths()
    {
        return $this->metadata['paths'];
    }

    /**
     * Get the root attribute of the embedded
     *
     * @return string
     */
    public function rootAttribute()
    {
        return $this->paths()[0];
    }

    /**
     * Check if the embedded is in root entity (not a deep embedded)
     *
     * @return bool
     */
    public function isRoot()
    {
        return $this->metadata['parentPath'] === 'root';
    }

    /**
     * Check if the embedded object is an entity
     *
     * @return bool
     */
    public function isEntity()
    {
        foreach ($this->classes() as $class) {
            if (!$this->resolver->isEntity($class)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the embedded object is importable object
     *
     * @return bool
     */
    public function isImportable()
    {
        foreach ($this->classes() as $class) {
            if (!$this->resolver->isImportable($class)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get list of classes for the entity
     *
     * @return string[]
     */
    public function classes()
    {
        if (!empty($this->metadata['polymorph'])) {
            return $this->metadata['class_map'];
        }

        return $this->metadata['classes'] ?? [$this->metadata['class']];
    }

    /**
     * @return string
     */
    public function class()
    {
        $classes = $this->classes();

        return reset($classes);
    }

    /**
     * Get the instantiator hint
     *
     * @param string|null $className
     *
     * @return mixed
     */
    public function hint($className = null)
    {
        if ($this->isPolymorph()) {
            return $this->metadata['hints'][$className];
        }

        return $this->metadata['hint'] ?? null;
    }

    /**
     * Get the discriminator database field name
     *
     * @return string
     */
    public function discriminatorField()
    {
        return $this->metadata['discriminator_field'];
    }

    /**
     * Check if the embedded is polymorph
     *
     * @return bool
     */
    public function isPolymorph()
    {
        return !empty($this->metadata['polymorph']);
    }

    /**
     * @return array
     */
    public function metadata()
    {
        return $this->metadata;
    }

    /**
     * Get the parent embedded
     *
     * @return EmbeddedInfo
     */
    public function parent()
    {
        return $this->resolver->embedded($this->metadata['parentPath']);
    }

    /**
     * Get the parent property name for store the current embedded entity
     *
     * @return string
     */
    public function property()
    {
        if ($this->isRoot()) {
            return $this->path;
        }

        return substr($this->path, strlen($this->metadata['parentPath']) + 1);
    }
}
