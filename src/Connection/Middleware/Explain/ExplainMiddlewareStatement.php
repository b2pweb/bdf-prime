<?php

namespace Bdf\Prime\Connection\Middleware\Explain;

use Bdf\Prime\Connection\Middleware\Explain\Report\ExplainReporter;
use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;

/**
 * @internal
 */
final class ExplainMiddlewareStatement extends AbstractStatementMiddleware
{
    private Explainer $explainer;
    private ExplainReporter $reporter;
    private string $query;
    private array $parameters = [];
    private array $types = [];

    public function __construct(Statement $statement, Explainer $explainer, ExplainReporter $reporter, string $query)
    {
        parent::__construct($statement);

        $this->explainer = $explainer;
        $this->reporter = $reporter;
        $this->query = $query;
    }

    /**
     * {@inheritdoc}
     */
    public function bindValue($param, $value, $type = ParameterType::STRING)
    {
        $this->parameters[$param] = $value;
        $this->types[$param] = $type;

        return parent::bindValue($param, $value, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function bindParam($param, &$variable, $type = ParameterType::STRING, $length = null)
    {
        $this->parameters[$param] = &$variable;
        $this->types[$param] = $type;

        return parent::bindParam($param, $variable, $type, $length);
    }

    /**
     * {@inheritdoc}
     */
    public function execute($params = null): Result
    {
        $explain = $this->explainer->explain($this->query, $params ?? $this->parameters, $this->types);

        if ($explain) {
            $this->reporter->report($this->query, $explain);
        }

        return parent::execute($params);
    }
}
