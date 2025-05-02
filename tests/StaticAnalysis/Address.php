<?php

namespace StaticAnalysis;

use Bdf\Prime\Entity\Model;

/**
 * @api
 */
class Address extends Model
{
    public ?string $address = null;
    public ?string $zipCode = null;
    public ?string $city = null;
    public ?string $country = null;

    public function __construct(array $data = [])
    {
        $this->import($data);
    }
}
