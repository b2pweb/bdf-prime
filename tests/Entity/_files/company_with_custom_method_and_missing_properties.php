<?php

namespace Bdf\Prime;

/**
 * Company
 */
class Company
{
    /**
     * The company id
     *
     * @var integer
     */
    protected $id;

    /**
     * Set id
     *
     * @return $this
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     */
    public function id(): int
    {
        return $this->id;
    }

    public function isFoo(): bool
    {
        return $this->id && ($this->id % 3840) === 0;
    }

    public function isBar(): bool
    {
        return $this->id && ($this->id % 2980) === 0;
    }
}
