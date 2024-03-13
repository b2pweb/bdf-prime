<?php

namespace Bdf\Prime;

use Bdf\Prime\Entity\Model;

/**
 * PersonWithValueObject
 */
class PersonWithValueObject extends Model
{
    /**
     * @var PersonId
     */
    protected ?PersonId $id = null;

    /**
     * @var Name
     */
    protected Name $firstName;

    /**
     * @var Name
     */
    protected Name $lastName;

    /**
     * @var AddressWithValueObject
     */
    protected AddressWithValueObject $address;

    /**
     * Set id
     *
     * @return $this
     */
    public function setId(?PersonId $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     */
    public function id(): ?PersonId
    {
        return $this->id;
    }

    /**
     * Set firstName
     *
     * @return $this
     */
    public function setFirstName(Name $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName
     */
    public function firstName(): Name
    {
        return $this->firstName;
    }

    /**
     * Set lastName
     *
     * @return $this
     */
    public function setLastName(Name $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName
     */
    public function lastName(): Name
    {
        return $this->lastName;
    }

    /**
     * Set address
     *
     * @return $this
     */
    public function setAddress(AddressWithValueObject $address): self
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     */
    public function address(): AddressWithValueObject
    {
        return $this->address;
    }

    public function __construct(array $data = [])
    {
        $this->firstName = \Bdf\Prime\Name::from('John');
        $this->lastName = \Bdf\Prime\Name::from('Doe');
        $this->address = new AddressWithValueObject();
        $this->import($data);
    }
}
