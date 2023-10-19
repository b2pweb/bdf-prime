<?php

namespace Php80\Mapper\_files;

use Bdf\Prime\Entity\Model;

class WithQuoteIdentifierEntity extends Model
{
    public function __construct(
        public ?int $id = null,
        public ?string $name = null
    ) {
    }
}
