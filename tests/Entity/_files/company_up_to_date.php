<?php

namespace Bdf\Prime;

/**
 * Company
 */
class Company
{
    /**
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

    /**
     * Set name
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
     */
    public function name(): string
    {
        return $this->name;
    }
}
