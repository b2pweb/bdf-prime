<?php

namespace Bdf\Prime;

use DateTime;

class PrimeSerializableEntity extends PrimeSerializable
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $email;

    /**
     * @var DateTime
     */
    public $subscriptionDate;


    /**
     * PrimeSerializableEntity constructor.
     *
     * @param string $name
     * @param string $email
     * @param DateTime $subscriptionDate
     */
    public function __construct($name, $email, DateTime $subscriptionDate)
    {
        $this->name = $name;
        $this->email = $email;
        $this->subscriptionDate = $subscriptionDate;
    }
}