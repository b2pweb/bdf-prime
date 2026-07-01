<?php

namespace Bdf\Prime\Connection\Middleware\DebugStack;

use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;
use Override;

use function array_values;

final class DebugStackStatement extends AbstractStatementMiddleware
{
    private array $parameters = [];

    public function __construct(
        Statement $wrappedStatement,
        private readonly DebugStack $debugStack,
        private readonly string $query,
    ) {
        parent::__construct($wrappedStatement);
    }

    #[Override]
    public function bindValue(int|string $param, mixed $value, ParameterType $type): void
    {
        $this->parameters[$param] = $value;

        parent::bindValue($param, $value, $type);
    }

    #[Override]
    public function execute(): Result
    {
        try {
            return parent::execute();
        } finally {
            $this->debugStack->push(new DebugQuery($this->query, array_values($this->parameters)));
        }
    }
}
