<?php

namespace Bdf\Prime\Query\Pagination\WalkStrategy;

use Bdf\Prime\Collection\CollectionInterface;
use Bdf\Prime\Query\Contract\Limitable;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\ReadCommandInterface;
use InvalidArgumentException;

/**
 * Simple walk strategy using pagination
 * This strategy do not handle write on entities during the walk (like delete entities)
 */
final class PaginationWalkStrategy implements WalkStrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function initialize(ReadCommandInterface $query, int $chunkSize, int $startPage): WalkCursor
    {
        if (!$query instanceof Limitable) {
            throw new InvalidArgumentException('The query must be an instance of '.Limitable::class);
        }

        $query = clone $query;
        $cursor = new WalkCursor($query);

        $cursor->cursor = ($startPage - 1) * $chunkSize;
        $query->limit($chunkSize);

        return $cursor;
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function next(WalkCursor $cursor): WalkCursor
    {
        $cursor = clone $cursor;
        /** @var Limitable&ReadCommandInterface $query */
        $query = $cursor->query;

        $query->offset($cursor->cursor);
        $cursor->cursor += $query->getLimit();
        $cursor->entities = $query->all();

        if ($cursor->entities instanceof CollectionInterface) {
            $cursor->entities = $cursor->entities->all();
        }

        return $cursor;
    }
}
