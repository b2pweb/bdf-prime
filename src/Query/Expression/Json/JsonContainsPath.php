<?php

namespace Bdf\Prime\Query\Expression\Json;

use Bdf\Prime\Query\CompilableClause as Q;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Compiler\QuoteCompilerInterface;
use Bdf\Prime\Query\Expression\ExpressionInterface;
use LogicException;

/**
 * Check if a JSON document or array contains the given path
 * This expression will generate the JSON_CONTAINS_PATH() function, or emulate it on SQLite with the IN operator and json_tree() function
 *
 * Usage:
 * <code>
 *     $query->whereRaw(new JsonContainsPath('json_field', '$.bar')); // Search rows that contains has the "bar" field the json_field
 * </code>
 */
final class JsonContainsPath implements ExpressionInterface
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

    /**
     * {@inheritdoc}
     */
    public function build(Q $query, object $compiler): string
    {
        if (!$compiler instanceof QuoteCompilerInterface || !$compiler instanceof CompilerInterface) {
            throw new LogicException('JsonContainsPath expression is not supported by the current compiler');
        }

        $target = $this->target instanceof ExpressionInterface
            ? $this->target->build($query, $compiler)
            : $compiler->quoteIdentifier($query, $query->preprocessor()->field($this->target))
        ;

        $dbms = $compiler->platform()->name();

        switch ($dbms) {
            case 'sqlite':
                return self::getSqliteExpression($compiler, $target, $this->path);

            default:
                return self::getDefaultExpression($compiler, $target, $this->path);
        }
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
