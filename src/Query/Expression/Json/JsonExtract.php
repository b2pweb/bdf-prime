<?php

namespace Bdf\Prime\Query\Expression\Json;

use Bdf\Prime\Query\CompilableClause as Q;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Compiler\QuoteCompilerInterface;
use Bdf\Prime\Query\Expression\ExpressionInterface;
use LogicException;

/**
 * Extract a value from a JSON field
 * Use ->> operator, or JSON_EXTRACT() function
 *
 * Note: the result JSON may differ depending on the DBMS
 *
 * @see https://mariadb.com/kb/en/jsonpath-expressions/ For the path syntax
 */
final class JsonExtract implements ExpressionInterface
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
    public function build(Q $query, object $compiler): string
    {
        if (!$compiler instanceof QuoteCompilerInterface || !$compiler instanceof CompilerInterface) {
            throw new LogicException('JsonExtract expression is not supported by the current compiler');
        }

        $field = $compiler->quoteIdentifier($query, $query->preprocessor()->field($this->field));
        $dbms = $compiler->platform()->name();

        return sprintf(
            self::getExpression($dbms, $this->unquote),
            $field,
            (string) $compiler->quote($this->path)
        );
    }

    private static function getExpression(string $dbms, bool $unquote): string
    {
        if ($dbms === 'sqlite') {
            return $unquote ? '%s->>%s' : '%s->%s';
        }

        $expression = 'JSON_EXTRACT(%s, %s)';

        if ($unquote) {
            $expression = 'JSON_UNQUOTE('.$expression.')';
        }

        return $expression;
    }
}
