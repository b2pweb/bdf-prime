<?php

namespace Bdf\Prime\Platform;

/**
 * Abstract type class for platform types
 */
abstract class AbstractPlatformType implements PlatformTypeInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var PlatformInterface
     */
    protected $platform;


    /**
     * {@inheritdoc}
     */
    public function __construct(PlatformInterface $platform, $name)
    {
        $this->platform = $platform;
        $this->name     = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value, array $fieldOptions = [])
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function toDatabase($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return $this->name;
    }
}
