<?php

namespace Bdf\Prime\Connection\Middleware\DebugStack;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Override;

final class DebugStackConnection extends AbstractConnectionMiddleware
{
    public function __construct(
        Connection $wrappedConnection,
        private readonly DebugStack $debugStack,
    ) {
        parent::__construct($wrappedConnection);
    }

    #[Override]
    public function query(string $sql): Result
    {
        try {
            return parent::query($sql);
        } finally {
            $this->debugStack->push(new DebugQuery($sql, []));
        }
    }

    #[Override]
    public function exec(string $sql): int|string
    {
        try {
            return parent::exec($sql);
        } finally {
            $this->debugStack->push(new DebugQuery($sql, []));
        }
    }

    #[Override]
    public function prepare(string $sql): Statement
    {
        return new DebugStackStatement(parent::prepare($sql), $this->debugStack, $sql);
    }
}
