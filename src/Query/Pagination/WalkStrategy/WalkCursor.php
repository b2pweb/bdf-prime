<?php

namespace Bdf\Prime\Query\Pagination\WalkStrategy;

use Bdf\Prime\Query\ReadCommandInterface;

/**
 * @internal
 */
final class WalkCursor
{
    /**
     * @var ReadCommandInterface
     */
    public $query;

    /**
     * @var mixed
     */
    public $cursor = null;

    /**
     * @var array|null
     */
    public $entities;

    /**
     * WalkCursor constructor.
     *
     * @param ReadCommandInterface $query
     */
    public function __construct(ReadCommandInterface $query)
    {
        $this->query = $query;
    }
}
