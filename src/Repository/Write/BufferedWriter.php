<?php

namespace Bdf\Prime\Repository\Write;

use Bdf\Event\EventNotifier;
use Bdf\Prime\Events;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Contract\WriteOperation;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Prime\Repository\RepositoryEventsSubscriberInterface;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * Store write operations to be executed at once on flush
 * It act as transaction for single repository at domain level
 *
 * <code>
 * $writer = new BufferedWriter($repository, $repository->writer());
 *
 * // Append operations
 * $writer->insert(...);
 * $writer->update(...);
 * // ...
 *
 * // Execute all operations
 * $writer->flush();
 *
 * // Cancel all operations
 * $writer->clear();
 * </code>
 *
 * Note 1: If there is pending operations, there were flushed by the object destructor
 * Note 2: Because operations will be performed later, the WriterInterface methods will always returns 1
 *
 * @template E as object
 * @implements WriterInterface<E>
 */
class BufferedWriter implements WriterInterface
{
    /**
     * @var RepositoryInterface&RepositoryEventsSubscriberInterface
     */
    private $repository;

    /**
     * @var WriterInterface
     */
    private $writer;

    /**
     * @var array
     */
    private $insert = [];

    /**
     * @var array
     */
    private $update = [];

    /**
     * @var array
     */
    private $delete = [];


    /**
     * BufferedWriter constructor.
     *
     * @param RepositoryEventsSubscriberInterface&RepositoryInterface $repository The owner repository where operation should be performed
     * @param WriterInterface|null $writer The base writer. If not provided, will use the repository writer
     */
    public function __construct(RepositoryInterface $repository, ?WriterInterface $writer = null)
    {
        $this->repository = $repository;
        $this->writer = $writer ?: $repository->writer();
    }

    /**
     * {@inheritdoc}
     */
    public function insert($entity, array $options = []): int
    {
        $this->insert[] = [$entity, $options];

        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function update($entity, array $options = []): int
    {
        $this->update[] = [$entity, $options];

        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity, array $options = []): int
    {
        $this->delete[] = [$entity, $options];

        return 1;
    }

    /**
     * Get pending operations count
     *
     * @return int
     */
    public function pending(): int
    {
        return count($this->insert) + count($this->update) + count($this->delete);
    }

    /**
     * Flush pending operations
     *
     * @return int Number of affected rows
     * @throws PrimeException When pending query fail
     */
    #[WriteOperation]
    public function flush(): int
    {
        try {
            return $this->flushInsert() + $this->flushUpdate() + $this->flushDelete();
        } finally {
            $this->clear();
        }
    }

    /**
     * Clear pending operations
     */
    public function clear(): void
    {
        $this->insert = [];
        $this->update = [];
        $this->delete = [];
    }

    /**
     * @return int
     * @throws PrimeException
     */
    private function flushInsert()
    {
        $count = 0;

        foreach ($this->insert as list($entity, $options)) {
            $count += $this->writer->insert($entity, $options);
        }

        return $count;
    }

    /**
     * @return int
     * @throws PrimeException
     */
    private function flushUpdate()
    {
        $count = 0;

        foreach ($this->update as list($entity, $options)) {
            $count += $this->writer->update($entity, $options);
        }

        return $count;
    }

    /**
     * @return int
     * @throws PrimeException
     */
    private function flushDelete()
    {
        /** @var EntityRepository $this->repository */
        if (empty($this->delete)) {
            return 0;
        }

        // Do not perform bulk delete for one operation
        if (count($this->delete) === 1) {
            return $this->writer->delete($this->delete[0][0], $this->delete[0][1]);
        }

        $toDelete = [];

        foreach ($this->delete as list($entity, $options)) {
            if ($this->repository->notify(Events::PRE_DELETE, [$entity, $this->repository]) !== false) {
                $toDelete[] = $entity;
            }
        }

        if (empty($toDelete)) {
            return 0;
        }

        $count = $this->repository->queries()->entities($toDelete)->delete();

        foreach ($toDelete as $entity) {
            $this->repository->notify(Events::POST_DELETE, [$entity, $this->repository, $count]);
        }

        return $count;
    }

    /**
     * Flush on destruct
     * @throws PrimeException
     */
    public function __destruct()
    {
        $this->flush();
    }
}
