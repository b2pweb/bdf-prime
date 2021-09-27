<?php

namespace Bdf\Prime\Entity\Hydrator\Generator;

use ReflectionProperty;

/**
 * Store info about attribute
 */
class AttributeInfo
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $metadata;

    /**
     * @var AttributesResolver
     */
    private $resolver;

    /**
     * @var ReflectionProperty|null
     */
    private $reflection;


    /**
     * AttributeInfo constructor.
     *
     * @param string $name
     * @param array $metadata
     * @param AttributesResolver $resolver
     */
    public function __construct(string $name, array $metadata, AttributesResolver $resolver)
    {
        $this->name = $name;
        $this->metadata = $metadata;
        $this->resolver = $resolver;
    }

    /**
     * Check if the attribute is into an embedded object
     *
     * @return bool
     */
    public function isEmbedded()
    {
        return isset($this->metadata['embedded'])
            // If the attribute is a root attribute, check only for root embedded entities
            && (empty($this->metadata['root']) || $this->resolver->hasRootEmbedded($this->metadata['embedded']))
        ;
    }

    /**
     * Get the embedded metadata
     *
     * @return EmbeddedInfo
     */
    public function embedded()
    {
        return empty($this->metadata['root'])
            ? $this->resolver->embedded($this->metadata['embedded'])
            : $this->resolver->rootEmbedded($this->metadata['embedded'])
        ;
    }

    /**
     * Get the attribute name
     *
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Get the property name of the embedded object
     *
     * @return string
     */
    public function property()
    {
        $finalAttribute = explode('.', $this->name);

        return end($finalAttribute);
    }

    /**
     * Get the declared field type
     * If there is no declared type (like with root attributes), this method will return null
     *
     * @return string|null
     */
    public function type()
    {
        return $this->metadata['type'] ?? null;
    }

    /**
     * Get the database field name
     *
     * @return string
     */
    public function field()
    {
        return $this->metadata['field'];
    }

    /**
     * Get the class name of the entity which contains the given attribute
     *
     * @return class-string
     */
    public function containerClassName(): string
    {
        if (!empty($this->metadata['root']) || !$this->isEmbedded()) {
            return $this->resolver->className();
        }

        return $this->embedded()->class(); // @todo polymorph ?
    }

    /**
     * Get the php options of the field
     */
    public function phpOptions(): array
    {
        return $this->metadata['phpOptions'];
    }

    /**
     * Get the ReflectionProperty instance for the current attribute
     *
     * @return ReflectionProperty
     * @throws \ReflectionException
     */
    public function reflection(): ReflectionProperty
    {
        if (!$this->reflection) {
            $this->reflection = new ReflectionProperty($this->containerClassName(), $this->property());
        }

        return $this->reflection;
    }

    /**
     * Check if the property on the entity is typed (PHP >= 7.4)
     *
     * @return bool true if a type is defined
     *
     * @throws \ReflectionException
     */
    public function isTyped(): bool
    {
        if (PHP_VERSION_ID < 70400) {
            return false;
        }

        /** @psalm-suppress UndefinedMethod */
        return $this->reflection()->hasType();
    }

    /**
     * Check if the property on the entity allows null
     * To allow null it must be not typed or with nullable type
     *
     * @return bool
     * @throws \ReflectionException
     */
    public function isNullable(): bool
    {
        /** @psalm-suppress UndefinedMethod */
        return !$this->isTyped() || $this->reflection()->getType()->allowsNull();
    }

    /**
     * Check if the property on the entity has a default value, or is initilized with null
     *
     * @return bool
     * @throws \ReflectionException
     */
    public function isInitializedByDefault(): bool
    {
        return !$this->isTyped() || array_key_exists($this->property(), (new \ReflectionClass($this->containerClassName()))->getDefaultProperties());
    }
}
