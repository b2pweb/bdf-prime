<?php

namespace Query\Expression;

use BadMethodCallException;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\Sql\SqlPlatform;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\CompilableClause as Q;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Expression\AbstractPlatformSpecificExpression;
use Bdf\Prime\TestEntity;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use LogicException;
use PHPUnit\Framework\TestCase;

class AbstractPlatformSpecificExpressionTest extends TestCase
{
    use PrimeTestCase;

    protected function tearDown(): void
    {
        $this->unsetPrime();
    }

    public function test_default_implementation()
    {
        $expr = new class extends AbstractPlatformSpecificExpression { };

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('The expression ' . get_class($expr) . ' is not supported by the platform Bdf\Prime\Platform\Sql\SqlPlatform');

        $this->setupConnection('sqlite::memory:');

        TestEntity::where('name', $expr)->toSql();
    }

    public function test_should_forward_to_buildForUnknownPlatform()
    {
        $expr = new class extends AbstractPlatformSpecificExpression {
            protected function buildForUnknownPlatform(Q $query, CompilerInterface $compiler, PlatformInterface $platform, object $grammar): string
            {
                return 'foo';
            }
        };

        $this->setupConnection('sqlite::memory:');

        $this->assertSame('SELECT t0.* FROM test_ t0 WHERE t0.name = foo', TestEntity::where('name', $expr)->toSql());
    }

    public function test_should_discriminate_platform()
    {
        $expr = new class extends AbstractPlatformSpecificExpression {
            protected function buildForMySql(Q $query, CompilerInterface $compiler, SqlPlatform $platform, AbstractMySQLPlatform $grammar): string
            {
                return 'mysql';
            }

            protected function buildForSqlite(Q $query, CompilerInterface $compiler, SqlPlatform $platform, SqlitePlatform $grammar): string
            {
                return 'sqlite';
            }

            protected function buildForGenericSql(Q $query, CompilerInterface $compiler, SqlPlatform $platform, AbstractPlatform $grammar): string
            {
                return 'generic';
            }
        };

        $this->setupConnection('sqlite::memory:');
        $this->assertSame('SELECT t0.* FROM test_ t0 WHERE t0.name = sqlite', TestEntity::where('name', $expr)->toSql());

        $this->setupConnection(MYSQL_CONNECTION_DSN);
        $this->assertSame('SELECT t0.* FROM test_ t0 WHERE t0.name = mysql', TestEntity::where('name', $expr)->toSql());

        $this->setupConnection([
            'adapter' => 'sqlite',
            'memory' => true,
            'platform' => new PostgreSQLPlatform(),
        ]);
        $this->assertSame('SELECT t0.* FROM test_ t0 WHERE t0.name = generic', TestEntity::where('name', $expr)->toSql());
    }

    public function test_invalid_compiler()
    {
        $expr = new class extends AbstractPlatformSpecificExpression { };

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('The expression ' . get_class($expr) . ' is not supported by the current compiler');

        $expr->build($this->createMock(CompilableClause::class), new \stdClass());
    }

    public function test_invalid_platform()
    {
        $expr = new class extends AbstractPlatformSpecificExpression { };
        $compiler = $this->createMock(CompilerInterface::class);
        $platform = $this->createMock(PlatformInterface::class);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The platform ' . get_class($platform) . ' does not support the method apply().');

        $compiler->expects($this->once())->method('platform')->willReturn($platform);

        $expr->build($this->createMock(CompilableClause::class), $compiler);
    }

    private function setupConnection($dsn): void
    {
        $this->configurePrime();

        $this->prime()->connections()->removeConnection('test');
        $this->prime()->connections()->declareConnection('test', $dsn);
    }
}
