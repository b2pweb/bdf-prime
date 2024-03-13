<?php

namespace Bdf\Prime\Query\Expression\Json;

use Bdf\Prime\Platform\Sql\SqlPlatform;
use Bdf\Prime\Query\CompilableClause as Q;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Compiler\QuoteCompilerInterface;
use Bdf\Prime\Query\Expression\AbstractPlatformSpecificExpression;
use Bdf\Prime\Query\Expression\ExpressionInterface;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
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
final class ToJson extends AbstractPlatformSpecificExpression
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
    protected function buildForSqlite(Q $query, CompilerInterface $compiler, SqlPlatform $platform, SqlitePlatform $grammar): string
    {
        if (!$compiler instanceof QuoteCompilerInterface) {
            throw new LogicException('ToJson expression is not supported by the current compiler');
        }

        $value = $this->value;

        if ($value instanceof ExpressionInterface) {
            $value = $value->build($query, $compiler);
        } else {
            $value = $compiler->quote(json_encode($value));
        }

        return sprintf('json(%s)', (string) $value);
    }

    /**
     * {@inheritdoc}
     */
    protected function buildForMySql(Q $query, CompilerInterface $compiler, SqlPlatform $platform, AbstractMySQLPlatform $grammar): string
    {
        if (!$compiler instanceof QuoteCompilerInterface) {
            throw new LogicException('ToJson expression is not supported by the current compiler');
        }

        $value = $this->value;

        if ($value instanceof ExpressionInterface) {
            $value = $value->build($query, $compiler);
        } else {
            $value = $compiler->quote(json_encode($value));
        }

        $function = $grammar instanceof MariaDBPlatform ? 'JSON_COMPACT(%s)' : 'CAST(%s AS JSON)';

        return sprintf($function, (string) $value);
    }

    /**
     * {@inheritdoc}
     */
    protected function buildForGenericSql(Q $query, CompilerInterface $compiler, SqlPlatform $platform, AbstractPlatform $grammar): string
    {
        if (!$compiler instanceof QuoteCompilerInterface) {
            throw new LogicException('ToJson expression is not supported by the current compiler');
        }

        $value = $this->value;

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
     * @param QuoteCompilerInterface $compiler
     * @param mixed $value
     *
     * @return string
     */
    private function convertValue(QuoteCompilerInterface $compiler, $value): string
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
