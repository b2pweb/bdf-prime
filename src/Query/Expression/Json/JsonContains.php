<?php

namespace Bdf\Prime\Query\Expression\Json;

use Bdf\Prime\Query\CompilableClause as Q;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Compiler\QuoteCompilerInterface;
use Bdf\Prime\Query\Expression\ExpressionInterface;
use InvalidArgumentException;
use LogicException;

use phpDocumentor\Reflection\Types\Scalar;

use function get_debug_type;
use function is_scalar;
use function is_string;
use function json_encode;

/**
 * Check if a JSON document or array contains a specific value
 * This expression will generate the JSON_CONTAINS() function, or emulate it on SQLite with the IN operator and json_each() function
 *
 * Usage:
 * <code>
 *     $query->whereRaw(new JsonContains('json_field', 'value')); // Search rows that contains the value "value" in the json_field
 *     $query->whereRaw(new JsonContains(new JsonExtract('json_field', '$.tags', false), 'value')); // Search from a nested field "tags". Note that the value is not unquoted
 * </code>
 */
final class JsonContains implements ExpressionInterface
{
    /**
     * @var string|ExpressionInterface
     */
    private $target;

    /**
     * @var scalar
     */
    private $candidate;

    /**
     * @param ExpressionInterface|string $target The JSON document or array to search in. Can be an attribute name, or a SQL expression. The value should not be unquoted.
     * @param scalar $candidate The value to search for. This value will be escaped.
     */
    public function __construct($target, $candidate)
    {
        if (!is_scalar($candidate)) {
            throw new InvalidArgumentException('The candidate value must be a scalar ' . get_debug_type($candidate) . ' given');
        }

        $this->target = $target;
        $this->candidate = $candidate;
    }

    /**
     * {@inheritdoc}
     */
    public function build(Q $query, object $compiler): string
    {
        if (!$compiler instanceof QuoteCompilerInterface || !$compiler instanceof CompilerInterface) {
            throw new LogicException('JsonContains expression is not supported by the current compiler');
        }

        $target = $this->target instanceof ExpressionInterface
            ? $this->target->build($query, $compiler)
            : $compiler->quoteIdentifier($query, $query->preprocessor()->field($this->target))
        ;

        $dbms = $compiler->platform()->name();

        switch ($dbms) {
            case 'sqlite':
                return self::getSqliteExpression($compiler, $target, $this->candidate);

            default:
                return self::getDefaultExpression($compiler, $target, $this->candidate);
        }
    }

    /**
     * @param QuoteCompilerInterface&CompilerInterface $compiler
     * @param string $target
     * @param scalar $candidate
     *
     * @return string
     */
    private static function getSqliteExpression(QuoteCompilerInterface $compiler, string $target, $candidate): string
    {
        $candidate = is_string($candidate) ? $compiler->quote($candidate) : $candidate;

        return $candidate . ' IN (SELECT atom FROM json_each(' . $target . '))';
    }

    /**
     * @param QuoteCompilerInterface&CompilerInterface $compiler
     * @param string $target
     * @param scalar $candidate
     *
     * @return string
     */
    private static function getDefaultExpression(CompilerInterface $compiler, string $target, $candidate): string
    {
        $candidate = $compiler->quote(json_encode($candidate));

        return 'JSON_CONTAINS(' . $target . ', ' . $candidate . ')';
    }
}
