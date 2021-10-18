<?php

namespace Bdf\Prime\Mapper\Builder;

/**
 * Field builder for polymorph embedded
 */
class PolymorphBuilder extends FieldBuilder
{
    /**
     * @var string
     */
    private $discriminator;

    /**
     * Set the current field as type discriminator
     *
     * @return static
     */
    public function discriminator(): self
    {
        $this->nillable(false); // Force not nillable
        $this->discriminator = $this->current;

        return $this;
    }

    /**
     * Get the discriminator Database field
     *
     * @return string
     */
    public function getDiscriminatorField()
    {
        return $this[$this->discriminator]['alias'] ?? $this->discriminator;
    }

    /**
     * Get the discriminator PHP attribute name
     *
     * @return string
     */
    public function getDiscriminatorAttribute()
    {
        return $this->discriminator;
    }
}
