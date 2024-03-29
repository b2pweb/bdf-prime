<?php

namespace Bdf\Prime\Mapper\Info;

use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Types\PhpTypeInterface;
use Bdf\Prime\Types\TypeInterface;
use Bdf\Prime\Types\TypesRegistryInterface;
use DateTimeInterface;

/**
 * PropertyInfo
 */
class PropertyInfo implements InfoInterface
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
     * The types registry
     *
     * @var TypesRegistryInterface
     */
    protected $typesRegistry;


    /**
     * Constructor
     *
     * @param string $name The property name
     * @param array $metadata The property metadata or the relation metadata
     * @param TypesRegistryInterface|null $typesRegistry The types registry
     */
    public function __construct(string $name, array $metadata = [], ?TypesRegistryInterface $typesRegistry = null)
    {
        $this->name = $name;
        $this->metadata = $metadata;
        $this->typesRegistry = $typesRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get the property type
     *
     * @return string
     */
    public function type(): string
    {
        return $this->metadata['type'];
    }

    /**
     * Get the property alias
     *
     * @return string|null
     */
    public function alias(): ?string
    {
        return $this->metadata['field'] !== $this->metadata['attribute']
            ? $this->metadata['field']
            : null;
    }

    /**
     * Get the php type of the property
     * Class should have '\' char to be resolved with namespace.
     *
     * @return string
     */
    public function phpType(): string
    {
        if (isset($this->metadata['phpOptions']['className'])) {
            return '\\'.ltrim($this->metadata['phpOptions']['className'], '\\');
        }

        $type = $this->type();

        if ($this->typesRegistry === null || !$this->typesRegistry->has($type)) {
            return $type;
        }

        return $this->typesRegistry->get($type)->phpType();
    }

    /**
     * Check whether the property is a primary key
     *
     * @return bool
     */
    public function isPrimary(): bool
    {
        return $this->metadata['primary'] !== null;
    }

    /**
     * Check if the property value is auto-generated (auto increment or sequence)
     *
     * @return bool
     */
    public function isGenerated(): bool
    {
        return in_array($this->metadata['primary'], [Metadata::PK_AUTOINCREMENT, Metadata::PK_SEQUENCE], true);
    }

    /**
     * Check if the property can be null
     *
     * - Marked as nullable on mapper
     * - It's value is auto-generated
     *
     * @return bool
     */
    public function isNullable(): bool
    {
        return !empty($this->metadata['nillable']) || $this->isGenerated();
    }

    /**
     * {@inheritdoc}
     */
    public function isEmbedded(): bool
    {
        return $this->metadata['embedded'] !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function belongsToRoot(): bool
    {
        return !$this->isEmbedded();
    }

    /**
     * {@inheritdoc}
     */
    public function isObject(): bool
    {
        return false;
    }

    /**
     * Check whether the property is a date time object
     *
     * @return bool
     */
    public function isDateTime(): bool
    {
        return is_subclass_of($this->phpType(), DateTimeInterface::class);
    }

    /**
     * Gets the date timezone
     *
     * @return string|null
     */
    public function getTimezone(): ?string
    {
        if (!$this->isDateTime()) {
            return null;
        }

        if (isset($this->metadata['phpOptions']['timezone'])) {
            return $this->metadata['phpOptions']['timezone'];
        }

        /** @var \Bdf\Prime\Types\AbstractDateTimeType $type */
        $type = $this->getType();
        /** @var \DateTimeZone $timezone */
        $timezone = $type->getTimezone();

        return $timezone ? $timezone->getName() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function isArray(): bool
    {
        return $this->phpType() === PhpTypeInterface::TARRAY;
    }

    /**
     * Check whether the property has a default value
     *
     * @return bool
     */
    public function hasDefault(): bool
    {
        return $this->getDefault() !== null;
    }

    /**
     * Get the default value
     *
     * @return mixed
     */
    public function getDefault()
    {
        return $this->metadata['default'];
    }

    /**
     * Get the default value
     *
     * @param mixed $value
     * @param bool  $toPhp
     * @param array $fieldOptions
     *
     * @return mixed
     */
    public function convert($value, bool $toPhp = true, array $fieldOptions = [])
    {
        if ($toPhp) {
            return $this->getType()->fromDatabase($value, $fieldOptions);
        }

        return $this->getType()->toDatabase($value);
    }

    /**
     * Get the type object
     *
     * @return TypeInterface
     */
    protected function getType()
    {
        return $this->typesRegistry->get($this->type());
    }
}
