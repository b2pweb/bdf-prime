<?php

namespace Bdf\Prime\Platform\Sql;

use BadMethodCallException;
use Bdf\Prime\Platform\PlatformInterface;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;

use function get_class;

/**
 * Trait for implement {@see SqlPlatformOperationInterface}
 * This trait will simply forward the call to the more generic methods
 *
 * @psalm-require-implements SqlPlatformOperationInterface
 */
trait SqlPlatformOperationTrait
{
    /**
     * {@inheritdoc}
     */
    public function onGenericSqlPlatform(SqlPlatform $platform, AbstractPlatform $grammar)
    {
        return $this->onUnknownPlatform($platform, $grammar);
    }

    /**
     * {@inheritdoc}
     */
    public function onMysqlPlatform(SqlPlatform $platform, AbstractMySQLPlatform $grammar)
    {
        return $this->onGenericSqlPlatform($platform, $grammar);
    }

    /**
     * {@inheritdoc}
     */
    public function onSqlitePlatform(SqlPlatform $platform, SqlitePlatform $grammar)
    {
        return $this->onGenericSqlPlatform($platform, $grammar);
    }

    /**
     * {@inheritdoc}
     */
    public function onUnknownPlatform(PlatformInterface $platform, object $grammar)
    {
        throw new BadMethodCallException('The platform ' . get_class($platform) . ' is not supported by ' . static::class);
    }
}
