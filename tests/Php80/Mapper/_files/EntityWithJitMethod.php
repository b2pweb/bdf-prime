<?php

namespace Php80\Mapper\_files;

use Bdf\Prime\Entity\Model;
use Bdf\Prime\TestEntity;

class EntityWithJitMethod extends Model
{
    public ?TestEntity $rel = null;

    public function __construct(
        public ?int $id = null,
        public ?string $name = null
    ) {
    }
}
