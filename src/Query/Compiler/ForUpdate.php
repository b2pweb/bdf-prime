<?php

namespace Bdf\Prime\Query\Compiler;

use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\Sql\SqlPlatform;
use Bdf\Prime\Platform\Sql\SqlPlatformOperationInterface;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Override;

/**
 * @implements SqlPlatformOperationInterface<string>
 */
final readonly class ForUpdate implements SqlPlatformOperationInterface
{
    public function __construct(
        private LockMode $lock,
    ) {}

    #[Override]
    public function onUnknownPlatform(PlatformInterface $platform, object $grammar): string
    {
        return '';
    }

    #[Override]
    public function onGenericSqlPlatform(SqlPlatform $platform, AbstractPlatform $grammar): string
    {
        return match ($this->lock) {
            LockMode::PESSIMISTIC_WRITE => 'FOR UPDATE',
            LockMode::PESSIMISTIC_READ => 'FOR SHARE',
            default => '',
        };
    }

    #[Override]
    public function onMysqlPlatform(SqlPlatform $platform, AbstractMySQLPlatform $grammar): string
    {
        return match ($this->lock) {
            LockMode::PESSIMISTIC_WRITE => 'FOR UPDATE',
            LockMode::PESSIMISTIC_READ => 'LOCK IN SHARE MODE',
            default => '',
        };
    }

    #[Override]
    public function onSqlitePlatform(SqlPlatform $platform, SqlitePlatform $grammar): string
    {
        return '';
    }
}
