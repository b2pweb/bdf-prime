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
}
