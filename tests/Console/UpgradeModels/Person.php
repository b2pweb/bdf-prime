<?php

namespace Console\UpgradeModels;

use Bdf\Prime\Entity\InitializableInterface;
use Bdf\Prime\Entity\Model;

class Person extends Model implements InitializableInterface
{
    public $id;
    public $firstName;
    public $lastName;
    public $address;

    public function __construct(array $data = [])
    {
        $this->initialize();
        $this->import($data);
    }

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        $this->address = new Address();
    }
}
