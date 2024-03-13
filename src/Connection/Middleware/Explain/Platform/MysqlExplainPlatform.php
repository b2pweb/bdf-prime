<?php

namespace Bdf\Prime\Connection\Middleware\Explain\Platform;

use Bdf\Prime\Connection\Middleware\Explain\ExplainResult;
use Bdf\Prime\Connection\Middleware\Explain\ExplainStep;
use Bdf\Prime\Connection\Middleware\Explain\QueryType;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;

use function array_map;
use function explode;
use function stripos;
use function strtolower;
use function trim;

final class MysqlExplainPlatform implements ExplainPlatformInterface
{
    /**
     * {@inheritdoc}
     */
    public function compile(string $baseQuery): ?string
    {
        if (
            stripos($baseQuery, 'SELECT') !== 0
            && stripos($baseQuery, 'UPDATE') !== 0
            && stripos($baseQuery, 'DELETE') !== 0
        ) {
            return null;
        }

        return 'EXPLAIN '.$baseQuery;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(string $query, Result $result): ExplainResult
    {
        $rows = $result->fetchAllAssociative();
        $tables = SqlUtil::tables($query);

        $explain = new ExplainResult();
        $explain->covering = true;
        $explain->raw = $rows;

        foreach ($explain->raw as $rawStep) {
            $step = $this->parseStep($tables, $rawStep);

            $explain->steps[] = $step;

            if ($step->table) {
                $explain->tables[] = $step->table;
            }

            if ($step->index) {
                // MySQL may use merge index, so we split the index
                array_push($explain->indexes, ...explode(',', $step->index));
            }

            $explain->covering = $explain->covering && $step->covering;
            $explain->temporary = $explain->temporary || $step->temporary;
            $explain->type = QueryType::worst($explain->type, $step->type);

            if ($step->rows !== null) {
                $explain->rows += $step->rows;
            }
        }

        return $explain;
    }

    /**
     * {@inheritdoc}
     */
    public static function supports(AbstractPlatform $platform): bool
    {
        return $platform instanceof AbstractMySQLPlatform;
    }

    private function parseStep(array $tables, array $rawStep): ExplainStep
    {
        $step = new ExplainStep();
        $type = strtolower($rawStep['type'] ?? '');
        $extra = array_map(fn ($v) => trim($v), explode(';', strtolower($rawStep['Extra'] ?? '')));
        $table = $rawStep['table'] ?? null;

        if ($table) {
            $step->table = $tables[$table] ?? $table;
        }

        $step->index = $rawStep['key'] ?? null;
        $step->rows = isset($rawStep['rows']) ? (int) $rawStep['rows'] : null;
        $step->covering = in_array('using index', $extra);
        $step->temporary = in_array('start temporary', $extra)
            || in_array('end temporary', $extra)
            || in_array('using filesort', $extra)
            || in_array('using temporary', $extra)
        ;

        switch ($type) {
            case 'system':
            case 'const':
                $step->type = QueryType::CONST;
                break;

            case 'eq_ref':
            case 'ref':
            case 'ref_or_null':
            case 'unique_subquery':
                $step->type = QueryType::PRIMARY;
                break;

            case 'fulltext':
            case 'index_merge':
            case 'index_subquery':
            case 'range':
                $step->type = QueryType::INDEX;
                break;

            case 'index':
            case 'all':
                $step->type = QueryType::SCAN;
                break;

            default:
                if (
                    in_array('no tables used', $extra) !== false // Use a const expression (like "SELECT 1")
                    || in_array('impossible where', $extra) !== false
                    || in_array('const row not found', $extra) !== false
                    || in_array('impossible where noticed after reading const tables', $extra) !== false
                    || in_array('impossible having', $extra) !== false
                    || in_array('const row not found', $extra) !== false
                    || in_array('no matching row in const table', $extra) !== false
                    || in_array('zero limit', $extra) !== false
                ) {
                    $step->type = QueryType::CONST;
                }
        }

        return $step;
    }
}
