<?php

namespace Bdf\Prime\Connection\Middleware\Explain\Platform;

use Bdf\Prime\Connection\Middleware\Explain\ExplainResult;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * The explain grammar for a specific platform
 */
interface ExplainPlatformInterface
{
    /**
     * Transform the base SQL query to an explain query
     * If the platform does not support explain, or the query is not supported, return null
     *
     * If null is returned, the middleware will not execute the explain query
     *
     * Note: the query can be a prepared one, with parameters instead of values, so the compiler must be able to handle it
     *
     * @param string $baseQuery The base query
     *
     * @return string|null The explain query, or null if not supported
     */
    public function compile(string $baseQuery): ?string;

    /**
     * Parse the explain result to normalize it
     *
     * @param Result $result The explain result
     * @return mixed
     */
    public function parse(Result $result): ExplainResult;

    /**
     * Check if the given platform is supported by this explain platform
     *
     * @param AbstractPlatform $platform The platform to check
     * @return bool
     */
    public static function supports(AbstractPlatform $platform): bool;
}
