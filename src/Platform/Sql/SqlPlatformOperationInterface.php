<?php

namespace Bdf\Prime\Platform\Sql;

use Bdf\Prime\Platform\PlatformSpecificOperationInterface;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;

/**
 * Base type for operation that supports SQL platform
 *
 * Use {@see SqlPlatformOperationTrait} to implement this interface
 *
 * @template R
 * @extends PlatformSpecificOperationInterface<R>
 */
interface SqlPlatformOperationInterface extends PlatformSpecificOperationInterface
{
    /**
     * Fallback method when the specific platform is not found
     *
     * @param SqlPlatform $platform
     * @param AbstractPlatform $grammar
     *
     * @return R
     */
    public function onGenericSqlPlatform(SqlPlatform $platform, AbstractPlatform $grammar);

    /**
     * Apply operation on a MySQL platform (or compatible like MariaDB)
     *
     * @param SqlPlatform $platform
     * @param AbstractMySQLPlatform $grammar
     *
     * @return R
     */
    public function onMysqlPlatform(SqlPlatform $platform, AbstractMySQLPlatform $grammar);

    /**
     * Apply operation on a SQLite platform
     *
     * @param SqlPlatform $platform
     * @param SqlitePlatform $grammar
     *
     * @return R
     */
    public function onSqlitePlatform(SqlPlatform $platform, SqlitePlatform $grammar);
}
