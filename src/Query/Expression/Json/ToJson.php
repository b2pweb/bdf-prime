<?php

namespace Bdf\Prime\Query\Expression\Json;

use Bdf\Prime\Query\CompilableClause as Q;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Compiler\QuoteCompilerInterface;
use Bdf\Prime\Query\Expression\ExpressionInterface;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use LogicException;

use function array_is_list;
use function array_map;
use function get_debug_type;
use function implode;
use function is_array;
use function is_float;
use function is_int;
use function is_object;
use function is_string;
use function json_encode;
use function sprintf;

/**
 * Expression for convert a value to json
 */
final class ToJson implements ExpressionInterface
{
    /**
     * @var mixed|ExpressionInterface
     */
    private $value;

    /**
     * @param ExpressionInterface|mixed $value The value to convert. Can be a PHP value, or a raw SQL expression when using ExpressionInterface.
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function build(Q $query, object $compiler): string
    {
        if (!$compiler instanceof QuoteCompilerInterface || !$compiler instanceof CompilerInterface) {
            throw new LogicException('ToJson expression is not supported by the current compiler');
        }

        $dbms = $compiler->platform()->name();

        switch ($dbms) {
            case 'sqlite':
                return $this->buildSqliteExpression($query, $compiler, $this->value);

            case 'mysql':
                return $this->buildMysqlExpression($query, $compiler, $this->value);

            default:
                return $this->buildDefaultExpression($query, $compiler, $this->value);
        }
    }

    /**
     * @param QuoteCompilerInterface&CompilerInterface $compiler
     * @param mixed|ExpressionInterface $value
     *
     * @return string
     */
    private function buildSqliteExpression(Q $query, $compiler, $value): string
    {
        if ($value instanceof ExpressionInterface) {
            $value = $value->build($query, $compiler);
        } else {
            $value = $compiler->quote(json_encode($value));
        }

        return sprintf('json(%s)', (string) $value);
    }

    /**
     * @param QuoteCompilerInterface&CompilerInterface $compiler
     * @param mixed|ExpressionInterface $value
     *
     * @return string
     */
    private function buildMysqlExpression(Q $query, CompilerInterface $compiler, $value)
    {
        if ($value instanceof ExpressionInterface) {
            $value = $value->build($query, $compiler);
        } else {
            $value = $compiler->quote(json_encode($value));
        }

        $function = $compiler->platform()->grammar() instanceof MariaDBPlatform ? 'JSON_COMPACT(%s)' : 'CAST(%s AS JSON)';

        return sprintf($function, (string) $value);
    }

    /**
     * @param QuoteCompilerInterface&CompilerInterface $compiler
     * @param mixed|ExpressionInterface $value
     *
     * @return string
     */
    private function buildDefaultExpression(Q $query, $compiler, $value)
    {
        if (!$value instanceof ExpressionInterface) {
            return $this->convertValue($compiler, $value);
        }

        return $value->build($query, $compiler);
    }

    /**
     * Convert PHP value to JSON expression:
     * - string will be quoted
     * - int, float and boolean will be converted to string
     * - array list will be converted to JSON_ARRAY(...)
     * - array and object will be converted to JSON_OBJECT(...)
     *
     * @param QuoteCompilerInterface&CompilerInterface $compiler
     * @param mixed $value
     *
     * @return string
     */
    private function convertValue($compiler, $value): string
    {
        if (is_string($value)) {
            return (string) $compiler->quote($value);
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if ($value === true) {
            return 'true';
        }

        if ($value === null) {
            return 'null';
        }

        if ($value === false) {
            return 'false';
        }

        if (is_array($value) && array_is_list($value)) {
            return 'JSON_ARRAY(' . implode(', ', array_map(fn ($value) => $this->convertValue($compiler, $value), $value)) . ')';
        }

        if (is_array($value) || is_object($value)) {
            $value = (array) $value;
            $arguments = [];

            foreach ($value as $key => $item) {
                $arguments[] = $compiler->quote($key);
                $arguments[] = $this->convertValue($compiler, $item);
            }

            return 'JSON_OBJECT(' . implode(', ', $arguments) . ')';
        }

        throw new LogicException('Cannot convert value to JSON of type ' . get_debug_type($value));
    }
}
