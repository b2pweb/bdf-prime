<?php

namespace Bdf\Prime\Types;

use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\PlatformTypeInterface;

/**
 * Typed array database type
 */
class ArrayOfType implements FacadeTypeInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var TypeInterface
     */
    private $baseArray;

    /**
     * @var TypeInterface
     */
    private $innerType;


    /**
     * ArrayOfType constructor.
     *
     * @param TypeInterface $baseArray The base array type. Use for serialize the array into database
     * @param TypeInterface $innerType The element type. Use for serialize each array elements
     */
    public function __construct(TypeInterface $baseArray, TypeInterface $innerType)
    {
        $this->name = $innerType->name().'[]';
        $this->baseArray = $baseArray;
        $this->innerType = $innerType;
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value, array $fieldOptions = [])
    {
        if ($value === null) {
            return null;
        }

        return array_map([$this->innerType, 'fromDatabase'], $this->baseArray->fromDatabase($value, $fieldOptions));
    }

    /**
     * {@inheritdoc}
     */
    public function toDatabase($value)
    {
        if ($value === null) {
            return null;
        }

        return $this->baseArray->toDatabase(array_map([$this->innerType, 'toDatabase'], (array) $value));
    }

    /**
     * {@inheritdoc}
     */
    public function phpType(): string
    {
        return $this->baseArray->phpType();
    }

    /**
     * {@inheritdoc}
     */
    public function toPlatformType(PlatformInterface $platform): PlatformTypeInterface
    {
        $registry = $platform->types();

        return $registry->get($registry->isNative($this->baseArray->name()) ? $this->baseArray->name() : self::TEXT);
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return $this->name;
    }
}
