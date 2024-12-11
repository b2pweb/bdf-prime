<?php

namespace Platform\Sql;

use BadMethodCallException;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\Sql\SqlPlatform;
use Bdf\Prime\Platform\Sql\SqlPlatformOperationInterface;
use Bdf\Prime\Platform\Sql\SqlPlatformOperationTrait;
use Bdf\Prime\Types\TypesRegistry;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use PHPUnit\Framework\TestCase;

class SqlPlatformOperationTraitTest extends TestCase
{
    public function test_forward_to_onGenericSqlPlatform()
    {
        $op = new class implements SqlPlatformOperationInterface {
            use SqlPlatformOperationTrait;

            public function onGenericSqlPlatform(SqlPlatform $platform, AbstractPlatform $grammar)
            {
                return 'generic';
            }
        };

        $this->assertSame('generic', $op->onMysqlPlatform(new SqlPlatform(new MySQLPlatform(), new TypesRegistry()), new MySQLPlatform()));
        $this->assertSame('generic', $op->onSqlitePlatform(new SqlPlatform(new SqlitePlatform(), new TypesRegistry()), new SqlitePlatform()));
    }

    public function test_forward_to_onUnknownPlatform()
    {
        $op = new class implements SqlPlatformOperationInterface {
            use SqlPlatformOperationTrait;

            public function onUnknownPlatform(PlatformInterface $platform, object $grammar)
            {
                return 'unknown';
            }
        };

        $this->assertSame('unknown', $op->onMysqlPlatform(new SqlPlatform(new MySQLPlatform(), new TypesRegistry()), new MySQLPlatform()));
        $this->assertSame('unknown', $op->onSqlitePlatform(new SqlPlatform(new SqlitePlatform(), new TypesRegistry()), new SqlitePlatform()));
        $this->assertSame('unknown', $op->onGenericSqlPlatform(new SqlPlatform(new SqlitePlatform(), new TypesRegistry()), new SqlitePlatform()));
    }

    public function test_default_should_raise_exception()
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('The platform Bdf\Prime\Platform\Sql\SqlPlatform is not supported by ');

        $op = new class implements SqlPlatformOperationInterface {
            use SqlPlatformOperationTrait;
        };

        $op->onMysqlPlatform(new SqlPlatform(new MySQLPlatform(), new TypesRegistry()), new MySQLPlatform());
    }
}
