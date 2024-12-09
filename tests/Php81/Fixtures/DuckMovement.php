<?php

namespace Php81\Fixtures;

use Bdf\Prime\Entity\Model;

class DuckMovement extends Model
{
    public function __construct(
        public ?int $id = null,
        public ?int $duckId = null,
        public ?int $fromX = null,
        public ?int $fromY = null,
        public ?DirectionEnum $direction = null,
        public ?SpeedEnum $speed = null,
        public ?int $distance = null,
        public ?MovementMeanEnum $mean = null,
    ) {
    }
}
