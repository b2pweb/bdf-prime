<?php

namespace Bdf\Prime\Connection\Middleware\Explain\Platform;

use Bdf\Prime\Connection\Middleware\Explain\ExplainResult;
use Bdf\Prime\Connection\Middleware\Explain\ExplainStep;
use Bdf\Prime\Connection\Middleware\Explain\QueryType;
use Doctrine\DBAL\Driver\Result;

use Doctrine\DBAL\Platforms\AbstractPlatform;

use Doctrine\DBAL\Platforms\SqlitePlatform;

use function explode;
use function in_array;
use function preg_match;
use function stripos;
use function strpos;
use function strrchr;
use function substr;

/**
 * Perform EXPLAIN QUERY PLAN on SQLite
 * SELECT, UPDATE and DELETE are supported
 *
 * @see https://www.sqlite.org/eqp.html#the_explain_query_plan_command
 */
final class SqliteExplainPlatform implements ExplainPlatformInterface
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

        return 'EXPLAIN QUERY PLAN '.$baseQuery;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(Result $result): ExplainResult
    {
        $explain = new ExplainResult();
        $explain->covering = true;

        $explain->raw = $result->fetchAllAssociative();

        foreach ($explain->raw as $rawStep) {
            $step = $this->parseStep($rawStep['detail']);

            // The step does not contain any information (like "MULTI-INDEX")
            if (
                $step->type === QueryType::UNDEFINED
                && $step->table === null
                && !$step->temporary
            ) {
                continue;
            }

            $explain->steps[] = $step;

            if ($step->table) {
                $explain->tables[] = $step->table;
            }

            if ($step->index) {
                $explain->indexes[] = $step->index;
            }

            $explain->covering = $explain->covering && $step->covering;
            $explain->temporary = $explain->temporary || $step->temporary;
            $explain->type = QueryType::worst($explain->type, $step->type);
        }

        return $explain;
    }

    /**
     * {@inheritdoc}
     */
    public static function supports(AbstractPlatform $platform): bool
    {
        return $platform instanceof SqlitePlatform;
    }

    private function parseStep(string $detail): ExplainStep
    {
        $step = new ExplainStep();

        $step->type = $this->parseType($detail);

        $this->parseIndex($step, $detail);
        $step->table = $this->parseTable($detail);
        $step->temporary = strpos($detail, 'TEMP B-TREE') !== false;
        $step->extra = $detail;

        return $step;
    }

    private function parseType(string $detail): string
    {
        if (strpos($detail, 'CONSTANT ROW') !== false) {
            return QueryType::CONST;
        } elseif (strpos($detail, 'SEARCH') === 0) {
            return QueryType::INDEX;
        } elseif (strpos($detail, 'SCAN') === 0) {
            return QueryType::SCAN;
        }

        return QueryType::UNDEFINED;
    }

    private function parseIndex(ExplainStep $step, string $details): void
    {
        // Do not consider an "automatic" index as an index because it's created on the fly
        if (strpos($details, ' AUTOMATIC ') !== false) {
            $step->type = QueryType::SCAN;
            return;
        }

        $start = strpos($details, ' USING ');

        if ($start === false) {
            return;
        }

        $value = substr($details, $start + 7);

        $end = strrpos($details, ' (');

        if ($end !== false) {
            $value = substr($value, 0, $end - $start - 7);
        }


        if (strpos($value, 'PRIMARY KEY') !== false) {
            $step->index = 'PRIMARY KEY';
            $step->type = QueryType::PRIMARY;
        } elseif (strpos($value, 'INDEX') !== false) {
            $step->index = substr(strrchr($value, ' '), 1);
        }

        $step->covering = strpos($value, 'COVERING') !== false;
    }

    private function parseTable(string $detail): ?string
    {
        $parts = explode(' ', $detail);

        // SQLite < 3.36 : format SCAN TABLE t1
        if (count($parts) >= 3 && $parts[1] === 'TABLE') {
            return $parts[2];
        }

        // SQLite >= 3.36 : format SCAN t1
        if (count($parts) >= 2 && in_array($parts[0], ['SCAN', 'SEARCH'], true) && $parts[1] !== 'CONSTANT') {
            return $parts[1];
        }

        return null;
    }
}
