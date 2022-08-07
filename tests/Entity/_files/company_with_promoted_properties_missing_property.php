<?php

namespace Bdf\Prime;

use Bdf\Prime\Entity\Model;

/**
 * Company
 */
class Company extends Model
{
    public function __construct(
        /**
         * @var integer
         */
        protected ?int $id = null,
    ) {
    }

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
}
