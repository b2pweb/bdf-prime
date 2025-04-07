<?php

namespace Bdf\Prime\Query\Expression\Json;

use Bdf\Prime\Platform\Sql\SqlPlatform;
use Bdf\Prime\Query\CompilableClause;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Compiler\QuoteCompilerInterface;
use Bdf\Prime\Query\Expression\AbstractPlatformSpecificExpression;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use LogicException;

use function sprintf;

/**
 * Extract a value from a JSON field
 * Use ->> operator, or JSON_EXTRACT() function
 *
 * Note: the result JSON may differ depending on the DBMS
 *
 * @see https://mariadb.com/kb/en/jsonpath-expressions/ For the path syntax
 *
 * @template Q as \Bdf\Prime\Query\CompilableClause&\Bdf\Prime\Query\Contract\Compilable
 * @template C as object
 *
 * @extends AbstractPlatformSpecificExpression<Q, C>
 */
final class JsonExtract extends AbstractPlatformSpecificExpression
{
    private string $field;
    private string $path;
    private bool $unquote;

    /**
     * @param string $field The field name to extract
     * @param string $path The path to extract. Should start with '$' which is the root of the JSON document.
     * @param bool $unquote Whether to unquote the result.
     *                      If true ->> operator will be used (or JSON_UNQUOTE(JSON_EXTRACT()) on MariaDB).
     *                      If false -> operator will be used (or JSON_EXTRACT() on MariaDB)
     */
    public function __construct(string $field, string $path, bool $unquote = true)
    {
        $this->field = $field;
        $this->path = $path;
        $this->unquote = $unquote;
    }

    /**
     * {@inheritdoc}
     */
    protected function buildForSqlite(CompilableClause $query, CompilerInterface $compiler, SqlPlatform $platform, SqlitePlatform $grammar): string
    {
        if (!$compiler instanceof QuoteCompilerInterface) {
            throw new LogicException('JsonExtract expression is not supported by the current compiler');
        }

        $field = $compiler->quoteIdentifier($query, $query->preprocessor()->field($this->field));

        return sprintf(
            $this->unquote ? '%s->>%s' : '%s->%s',
            $field,
            (string) $compiler->quote($this->path)
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function buildForGenericSql(CompilableClause $query, CompilerInterface $compiler, SqlPlatform $platform, AbstractPlatform $grammar): string
    {
        if (!$compiler instanceof QuoteCompilerInterface) {
            throw new LogicException('JsonExtract expression is not supported by the current compiler');
        }

        $field = $compiler->quoteIdentifier($query, $query->preprocessor()->field($this->field));

        return sprintf(
            self::getExpression($this->unquote),
            $field,
            (string) $compiler->quote($this->path)
        );
    }

    private static function getExpression(bool $unquote): string
    {
        $expression = 'JSON_EXTRACT(%s, %s)';

        if ($unquote) {
            $expression = 'JSON_UNQUOTE('.$expression.')';
        }

        return $expression;
    }
}
