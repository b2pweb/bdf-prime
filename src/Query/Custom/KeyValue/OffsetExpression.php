<?php

namespace Bdf\Prime\Query\Custom\KeyValue;

use Bdf\Prime\Platform\Sql\SqlPlatform;
use Bdf\Prime\Platform\Sql\SqlPlatformOperationInterface;
use Bdf\Prime\Platform\Sql\SqlPlatformOperationTrait;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;

/**
 * Generate the OFFSET expression (without LIMIT)
 *
 * @internal Use by compiler
 * @implements SqlPlatformOperationInterface<string>
 */
final class OffsetExpression implements SqlPlatformOperationInterface
{
    use SqlPlatformOperationTrait;

    private int $offset;

    /**
     * @param int $offset
     */
    public function __construct(int $offset)
    {
        $this->offset = $offset;
    }

    /**
     * {@inheritdoc}
     */
    public function onSqlitePlatform(SqlPlatform $platform, SqlitePlatform $grammar): string
    {
        return ' LIMIT -1 OFFSET '.$this->offset;
    }

    /**
     * {@inheritdoc}
     */
    public function onMysqlPlatform(SqlPlatform $platform, AbstractMySQLPlatform $grammar): string
    {
        return ' LIMIT 18446744073709551615 OFFSET '.$this->offset;
    }

    /**
     * {@inheritdoc}
     */
    public function onGenericSqlPlatform(SqlPlatform $platform, AbstractPlatform $grammar): string
    {
        return ' OFFSET '.$this->offset;
    }
}
