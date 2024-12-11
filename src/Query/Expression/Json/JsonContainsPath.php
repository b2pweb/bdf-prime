<?php

namespace Bdf\Prime\Query\Expression\Json;

use Bdf\Prime\Platform\Sql\SqlPlatform;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Compiler\QuoteCompilerInterface;
use Bdf\Prime\Query\Expression\AbstractPlatformSpecificExpression;
use Bdf\Prime\Query\Expression\ExpressionInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use LogicException;

/**
 * Check if a JSON document or array contains the given path
 * This expression will generate the JSON_CONTAINS_PATH() function, or emulate it on SQLite with the IN operator and json_tree() function
 *
 * Usage:
 * <code>
 *     $query->whereRaw(new JsonContainsPath('json_field', '$.bar')); // Search rows that contains has the "bar" field the json_field
 * </code>
 *
 * @template Q as \Bdf\Prime\Query\CompilableClause&\Bdf\Prime\Query\Contract\Compilable
 * @template C as object
 *
 * @extends AbstractPlatformSpecificExpression<Q, C>
 */
final class JsonContainsPath extends AbstractPlatformSpecificExpression
{
    /**
     * @var string|ExpressionInterface
     */
    private $target;
    private string $path;

    /**
     * @param ExpressionInterface|string $target The JSON document or array to search in. Can be an attribute name, or a SQL expression. The value should not be unquoted.
     * @param string $path The path to search in the JSON document. Must start with "$"
     */
    public function __construct($target, string $path)
    {
        $this->target = $target;
        $this->path = $path;
    }

    protected function buildForSqlite(CompilableClause $query, CompilerInterface $compiler, SqlPlatform $platform, SqlitePlatform $grammar): string
    {
        if (!$compiler instanceof QuoteCompilerInterface) {
            throw new LogicException('JsonContainsPath expression is not supported by the current compiler');
        }

        return self::getSqliteExpression(
            $compiler,
            $this->target($query, $compiler),
            $this->path
        );
    }

    protected function buildForGenericSql(CompilableClause $query, CompilerInterface $compiler, SqlPlatform $platform, AbstractPlatform $grammar): string
    {
        if (!$compiler instanceof QuoteCompilerInterface) {
            throw new LogicException('JsonContainsPath expression is not supported by the current compiler');
        }

        return self::getDefaultExpression(
            $compiler,
            $this->target($query, $compiler),
            $this->path
        );
    }

    /**
     * @param Q $query
     * @param CompilerInterface&QuoteCompilerInterface $compiler
     * @return string
     * @throws \Bdf\Prime\Exception\PrimeException
     */
    private function target(CompilableClause $query, CompilerInterface $compiler)
    {
        return $this->target instanceof ExpressionInterface
            ? $this->target->build($query, $compiler)
            : $compiler->quoteIdentifier($query, $query->preprocessor()->field($this->target))
        ;
    }

    /**
     * @param QuoteCompilerInterface&CompilerInterface $compiler
     * @param string $target
     * @param string $path
     *
     * @return string
     */
    private static function getSqliteExpression(QuoteCompilerInterface $compiler, string $target, string $path): string
    {
        return $compiler->quote($path) . ' IN (SELECT fullkey FROM json_tree(' . $target . '))';
    }

    /**
     * @param QuoteCompilerInterface&CompilerInterface $compiler
     * @param string $target
     * @param string $path
     *
     * @return string
     */
    private static function getDefaultExpression(CompilerInterface $compiler, string $target, string $path): string
    {
        return 'JSON_CONTAINS_PATH(' . $target . ', "all", ' . $compiler->quote($path) . ')';
    }
}
