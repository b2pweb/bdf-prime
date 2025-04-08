<?php

namespace Php84\Fixtures;

use Bdf\Prime\Entity\InitializableInterface;
use Bdf\Prime\Entity\Model;

class EntityWithPropertyHook extends Model implements InitializableInterface
{
    public const STATS_STRENGTH = 0;
    public const STATS_INTELLIGENCE = 1;
    public const STATS_AGILITY = 2;

    public int $strength {
        get => $this->stats[self::STATS_STRENGTH] ?? 0;
        set(int $value) {
            $this->stats[self::STATS_STRENGTH] = $value;
        }
    }

    public int $intelligence {
        get => $this->stats[self::STATS_INTELLIGENCE] ?? 0;
        set(int $value) {
            $this->stats[self::STATS_INTELLIGENCE] = $value;
        }
    }

    public int $agility {
        get => $this->stats[self::STATS_AGILITY] ?? 0;
        set(int $value) {
            $this->stats[self::STATS_AGILITY] = $value;
        }
    }

    public ?EntityWithGetterProperty $owner = null;

    public function __construct(
        public ?int $id = null,
        public ?string $name = null,
        public array $stats = [],
        public ?int $ownerId = null,
    ) {
        $this->initialize();
    }

    public function initialize(): void
    {
        $this->owner = $this->relation('owner')->proxy();
    }
}
