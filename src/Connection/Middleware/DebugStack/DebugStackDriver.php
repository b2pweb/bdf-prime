<?php

namespace Bdf\Prime\Connection\Middleware\DebugStack;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Override;
use SensitiveParameter;

final class DebugStackDriver extends AbstractDriverMiddleware
{
    public function __construct(
        Driver $wrappedDriver,
        private readonly DebugStack $debugStack,
    ) {
        parent::__construct($wrappedDriver);
    }

    #[Override]
    public function connect(#[SensitiveParameter] array $params): DriverConnection
    {
        return new DebugStackConnection(parent::connect($params), $this->debugStack);
    }
}
