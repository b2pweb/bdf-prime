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
     * @var string
     */
    protected $name;

    /**
     * Set id
     *
     * @param integer $id
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
     *
     * @return integer
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

    /**
     * Set name
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }
}
