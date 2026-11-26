<?php

namespace Bdf\Prime\Record;

use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Query\Contract\Projectionable;
use Bdf\Prime\Query\Expression\ExpressionInterface;

/**
 * Base type for record's constructor parameter
 */
interface RecordParameterInterface
{
    /**
     * Get the required projection for the current parameter
     *
     * @return array<int|string, string|ExpressionInterface>
     * @see Projectionable::project() For the format
     */
    public function projection(): array;

    /**
     * Extract the parameter value from the database row
     *
     * @param PlatformInterface $platform The current database connection platform
     * @param array $data The database row
     *
     * @return mixed
     */
    public function value(PlatformInterface $platform, array $data): mixed;
}
