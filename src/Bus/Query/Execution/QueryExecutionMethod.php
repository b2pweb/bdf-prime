<?php

namespace Bdf\Prime\Bus\Query\Execution;

use Bdf\Prime\Exception\EntityNotFoundException;
use Bdf\Prime\Query\Contract\Aggregatable;
use Bdf\Prime\Query\Contract\Paginable;
use Bdf\Prime\Query\Query;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Query\ReadCommandInterface;

/**
 * Enum for default query execution methods
 */
enum QueryExecutionMethod implements QueryExecutionMethodInterface
{
    /**
     * Perform {@see QueryInterface::all()}, to retrieve all results
     */
    case All;

    /**
     * Perform {@see Paginable::walk()}, to retrieve all results using a walker
     */
    case Walk;

    /**
     * Perform {@see Paginable::paginate()}, to retrieve a chunk of results,
     * wrapped in a paginator.
     * "limit" and "page" options can be passed to the execution method.
     */
    case Paginate;

    /**
     * Perform {@see QueryInterface::first()}, to retrieve the first result
     */
    case First;

    /**
     * Perform {@see QueryInterface::firstOrFail()}, to retrieve the first result,
     * or throws {@see EntityNotFoundException} if no result is found
     */
    case FirstOrFail;

    /**
     * Perform {@see QueryInterface::firstOrNew()}, to retrieve the first result,
     * or create a new instance if no result is found
     */
    case FirstOrNew;

    /**
     * Perform {@see Aggregatable::count()}, to get the number of results that would be returned
     */
    case Count;

    /**
     * {@inheritdoc}
     */
    public function execute(ReadCommandInterface $query, array $options = []): mixed
    {
        /** @var Query $query */
        return match ($this) {
            self::All => $query->all(),
            self::Walk => $query->walk($options['limit'] ?? null, $options['page'] ?? null),
            self::Paginate => $query->paginate($options['limit'] ?? null, $options['page'] ?? null),
            self::First => $query->first(),
            self::FirstOrFail => $query->firstOrFail(),
            self::FirstOrNew => $query->firstOrNew(),
            self::Count => $query->count(),
        };
    }
}
