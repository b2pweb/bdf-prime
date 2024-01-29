<?php

namespace Bdf\Prime\Query\Expression;

use BadMethodCallException;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\Sql\SqlPlatform;
use Bdf\Prime\Platform\Sql\SqlPlatformOperationInterface;
use Bdf\Prime\Platform\Sql\SqlPlatformOperationTrait;
use Bdf\Prime\Query\CompilableClause as Q;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;

use LogicException;

use function get_class;

/**
 * Base class for generate a SQL expression depending on the platform
 * To generate a platform specific expression, extends this class and implements the buildForXXX() methods
 *
 * @implements SqlPlatformOperationInterface<string>
 */
abstract class AbstractPlatformSpecificExpression implements ExpressionInterface, SqlPlatformOperationInterface
{
    use SqlPlatformOperationTrait;

    private Q $query;
    private CompilerInterface $compiler;

    /**
     * {@inheritdoc}
     */
    final public function build(Q $query, object $compiler)
    {
        // @todo create a dedicated interface for platform() getter ?
        if (!$compiler instanceof CompilerInterface) {
            throw new BadMethodCallException('The expression ' . static::class . 'is not supported by the current compiler');
        }

        $configured = clone $this;
        $configured->query = $query;
        $configured->compiler = $compiler;

        $platform = $compiler->platform();

        if (!method_exists($platform, 'apply')) {
            throw new LogicException('The platform ' . get_class($platform) . ' does not support the method apply().');
        }

        return $platform->apply($configured);
    }

    /**
     * Compile the expression for MySQL platform
     *
     * @param Q $query
     * @param CompilerInterface $compiler
     * @param SqlPlatform $platform
     * @param AbstractMySQLPlatform $grammar
     *
     * @return string
     */
    protected function buildForMySql(Q $query, CompilerInterface $compiler, SqlPlatform $platform, AbstractMySQLPlatform $grammar): string
    {
        return $this->buildForGenericSql($query, $compiler, $platform, $grammar);
    }

    /**
     * Compile the expression for SQLite platform
     *
     * @param Q $query
     * @param CompilerInterface $compiler
     * @param SqlPlatform $platform
     * @param SqlitePlatform $grammar
     *
     * @return string
     */
    protected function buildForSqlite(Q $query, CompilerInterface $compiler, SqlPlatform $platform, SqlitePlatform $grammar): string
    {
        return $this->buildForGenericSql($query, $compiler, $platform, $grammar);
    }

    /**
     * Compile the expression for generic SQL platform
     *
     * @param Q $query
     * @param CompilerInterface $compiler
     * @param SqlPlatform $platform
     * @param AbstractPlatform $grammar
     *
     * @return string
     */
    protected function buildForGenericSql(Q $query, CompilerInterface $compiler, SqlPlatform $platform, AbstractPlatform $grammar): string
    {
        return $this->buildForUnknownPlatform($query, $compiler, $platform, $grammar);
    }

    /**
     * Compile the expression for unknown platform
     *
     * @param Q $query
     * @param CompilerInterface $compiler
     * @param PlatformInterface $platform
     * @param object $grammar
     *
     * @return string
     */
    protected function buildForUnknownPlatform(Q $query, CompilerInterface $compiler, PlatformInterface $platform, object $grammar): string
    {
        throw new BadMethodCallException('The expression ' . static::class . 'is not supported by the platform ' . get_class($platform));
    }

    /**
     * {@inheritdoc}
     */
    final public function onGenericSqlPlatform(SqlPlatform $platform, AbstractPlatform $grammar)
    {
        return $this->buildForGenericSql($this->query, $this->compiler, $platform, $grammar);
    }

    /**
     * {@inheritdoc}
     */
    final public function onMysqlPlatform(SqlPlatform $platform, AbstractMySQLPlatform $grammar)
    {
        return $this->buildForMySql($this->query, $this->compiler, $platform, $grammar);
    }

    /**
     * {@inheritdoc}
     */
    final public function onSqlitePlatform(SqlPlatform $platform, SqlitePlatform $grammar)
    {
        return $this->buildForSqlite($this->query, $this->compiler, $platform, $grammar);
    }

    /**
     * {@inheritdoc}
     */
    final public function onUnknownPlatform(PlatformInterface $platform, object $grammar)
    {
        return $this->buildForUnknownPlatform($this->query, $this->compiler, $platform, $grammar);
    }
}
