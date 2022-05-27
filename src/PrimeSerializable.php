<?php

namespace Bdf\Prime;

use Bdf\Serializer\SerializerInterface;

/**
 * PrimeSerializable
 */
abstract class PrimeSerializable extends Locatorizable
{
    /**
     * Export attributes or all entity as array
     *
     * @param array $options {@link \Bdf\Serializer\Context\NormalizationContext}
     *
     * @return array
     */
    public function toArray(array $options = [])
    {
        return self::serializer()->toArray($this, $options);
    }

    /**
     * Instanciate an object from its normalized representation
     *
     * @param array $normalized
     *
     * @return mixed
     */
    public static function fromArray(array $normalized)
    {
        return self::serializer()->fromArray($normalized, static::class);
    }

    /**
     * Export attributes or all entity as json
     *
     * @param array $options {@link \Bdf\Serializer\Context\NormalizationContext}
     *
     * @return string
     */
    public function toJson(array $options = [])
    {
        return self::serializer()->toJson($this, $options);
    }

    /**
     * Instanciate an object from its json representation
     *
     * @param string $json
     *
     * @return mixed
     */
    public static function fromJson($json)
    {
        return self::serializer()->fromJson($json, static::class);
    }

    /**
     * @return SerializerInterface
     */
    protected static function serializer()
    {
        return self::locator()->serializer();
    }
}
