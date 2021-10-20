<?php

namespace Bdf\Prime\Types;

use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\PlatformTypeInterface;

/**
 * Abstract class adapter for facade type
 */
abstract class AbstractFacadeType implements FacadeTypeInterface
{
    /**
     * @var string
     */
    protected $type;


    /**
     * FacadeType constructor.
     *
     * @param string $type
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function toPlatformType(PlatformInterface $platform): PlatformTypeInterface
    {
        $registry = $platform->types();

        return $registry->get($registry->isNative($this->type) ? $this->type : $this->defaultType());
    }

    /**
     * Get the default type to use, if platform do not supports it
     *
     * @return string
     */
    abstract protected function defaultType();

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return $this->type;
    }
}
