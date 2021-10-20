<?php

namespace Bdf\Prime\Bench;

use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\PlatformTypesInterface;
use Bdf\Prime\Platform\Sql\SqlPlatform;
use Bdf\Prime\Types\ArrayType;
use Bdf\Prime\Types\JsonType;
use Bdf\Prime\Types\TypeInterface;
use Bdf\Prime\Types\TypesRegistry;
use Doctrine\DBAL\Platforms\MySQLPlatform;

class DummyPlatform implements PlatformInterface
{
    private $platform;

    function __construct()
    {
        $this->platform = new SqlPlatform(new MySQLPlatform(), new TypesRegistry([
            TypeInterface::TARRAY => ArrayType::class,
            TypeInterface::JSON   => JsonType::class,
        ]));
    }

    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return $this->platform->name();
    }

    /**
     * @inheritDoc
     */
    public function types(): PlatformTypesInterface
    {
        return $this->platform->types();
    }

    /**
     * @inheritDoc
     */
    public function grammar()
    {
        return $this->platform->grammar();
    }
}
