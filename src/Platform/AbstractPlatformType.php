<?php

namespace Bdf\Prime\Platform;

/**
 * Abstract type class for platform types
 */
abstract class AbstractPlatformType implements PlatformTypeInterface
{
    protected string $name;
    protected PlatformInterface $platform;


    /**
     * {@inheritdoc}
     */
    public function __construct(PlatformInterface $platform, string $name)
    {
        $this->platform = $platform;
        $this->name     = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase(mixed $value, array $fieldOptions = []): mixed
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function toDatabase(mixed $value): mixed
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return $this->name;
    }
}
