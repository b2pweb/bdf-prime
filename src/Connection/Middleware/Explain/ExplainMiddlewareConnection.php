<?php

namespace Bdf\Prime\Connection\Middleware\Explain;

use Bdf\Prime\Connection\Middleware\Explain\Report\ExplainReporter;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
final class ExplainMiddlewareConnection extends AbstractConnectionMiddleware
{
    private Explainer $explainer;
    private ExplainReporter $reporter;

    public function __construct(Connection $connection, Explainer $explainer, ExplainReporter $reporter)
    {
        parent::__construct($connection);

        $this->explainer = $explainer;
        $this->reporter = $reporter;
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $sql): Result
    {
        $explain = $this->explainer->explain($sql);

        if ($explain) {
            $this->reporter->report($sql, $explain);
        }

        return parent::query($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function exec(string $sql): int
    {
        $explain = $this->explainer->explain($sql);

        if ($explain) {
            $this->reporter->report($sql, $explain);
        }

        return parent::exec($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function prepare(string $sql): Statement
    {
        return new ExplainMiddlewareStatement(
            parent::prepare($sql),
            $this->explainer,
            $this->reporter,
            $sql
        );
    }
}
