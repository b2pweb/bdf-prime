<?php

namespace Php84\Fixtures;

use Bdf\Prime\Entity\Model;

class EntityWithAsymmetricVisibility extends Model
{
    public function __construct(
        public protected(set) ?int $id = null,
        public ?string $name = null,
        public private(set) ?string $secret = null,
    ) {
    }

    public function setSecret(?string $secret): void
    {
        $this->secret = $secret;
    }
}
