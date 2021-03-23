<?php

namespace Bdf\Prime\Query\Pagination\WalkStrategy;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Query\ReadCommandInterface;

/**
 * @internal
 * @template R as array|object
 */
final class WalkCursor
{
    /**
     * @var ReadCommandInterface<ConnectionInterface, R>
     */
    public $query;

    /**
     * @var mixed
     */
    public $cursor = null;

    /**
     * @var R[]|null
     */
    public $entities;

    /**
     * WalkCursor constructor.
     *
     * @param ReadCommandInterface<ConnectionInterface, R> $query
     */
    public function __construct(ReadCommandInterface $query)
    {
        $this->query = $query;
    }
}
