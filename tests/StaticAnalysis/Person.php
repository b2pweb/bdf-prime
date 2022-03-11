<?php

namespace StaticAnalysis;

use Bdf\Prime\Entity\Model;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class Person extends Model
{
    /**
     * @var string|null
     */
    protected $id;

    /**
     * @var string
     */
    protected $firstName;

    /**
     * @var string
     */
    protected $lastName;

    /**
     * @var \DateTime|null
     */
    protected $brithDate;

    public function __construct(array $data = [])
    {
        $this->import($data);
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param string|null $id
     * @return Person
     */
    public function setId(?string $id): Person
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     * @return Person
     */
    public function setFirstName(string $firstName): Person
    {
        $this->firstName = $firstName;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     * @return Person
     */
    public function setLastName(string $lastName): Person
    {
        $this->lastName = $lastName;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getBrithDate(): ?\DateTime
    {
        return $this->brithDate;
    }

    /**
     * @param \DateTime|null $brithDate
     * @return Person
     */
    public function setBrithDate(?\DateTime $brithDate): Person
    {
        $this->brithDate = $brithDate;
        return $this;
    }
}
