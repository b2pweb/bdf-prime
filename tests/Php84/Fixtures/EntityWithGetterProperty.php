<?php

namespace Php84\Fixtures;

use Bdf\Prime\Entity\Model;

class EntityWithGetterProperty extends Model
{
    public string $normalizedEmail {
        get {
            [$local, $domain] = explode('@', strtolower($this->email), 2);

            if (str_contains($local, '+')) {
                $local = strstr($local, '+', true);
            }

            return $local.'@'.$domain;
        }
    }

    /**
     * @var list<EntityWithPropertyHook>
     */
    public array $characters {
        get => $this->load('characters')->characters;
        set => $value;
    }

    public function __construct(
        public ?int $id = null,
        public ?string $email = null,
        public ?string $password = null,
    ) {
    }
}
