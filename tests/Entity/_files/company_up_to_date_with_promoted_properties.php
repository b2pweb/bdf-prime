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
        /**
         * @var string
         */
        protected ?string $name = null,
    ) {
    }

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
