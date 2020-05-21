<?php

namespace Bdf\Prime\Entity\Hydrator\Generator;

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
     * AttributeInfo constructor.
     *
     * @param string $name
     * @param array $metadata
     * @param AttributesResolver $resolver
     */
    public function __construct($name, array $metadata, AttributesResolver $resolver)
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
     *
     * @return string
     */
    public function type()
    {
        return $this->metadata['type'];
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
     * Get the php options of the field
     */
    public function phpOptions(): array
    {
        return $this->metadata['phpOptions'];
    }
}
