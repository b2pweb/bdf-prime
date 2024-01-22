<?php

namespace Bdf\Prime\Connection\Middleware\Explain\Platform;

use Bdf\Prime\Connection\Middleware\Explain\ExplainResult;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Null object for explain platform
 * This platform will never compile nor parse explain result
 */
final class NullExplainPlatform implements ExplainPlatformInterface
{
    /**
     * {@inheritdoc}
     */
    public function compile(string $baseQuery): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function parse(Result $result): ExplainResult
    {
        return new ExplainResult();
    }

    /**
     * {@inheritdoc}
     */
    public static function supports(AbstractPlatform $platform): bool
    {
        return true;
    }
}
