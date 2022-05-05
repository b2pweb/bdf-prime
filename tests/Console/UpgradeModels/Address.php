<?php

namespace Console\UpgradeModels;

use Bdf\Prime\Entity\Model;

class Address extends Model
{
    public $id;
    public $street;
    public $number;
    public $city;
    public $zipCode;
    public $country;

    public function __construct(array $data = [])
    {
        $this->import($data);
    }
}
