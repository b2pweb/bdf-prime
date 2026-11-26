<?php

namespace Bdf\Prime\Repository\Write;

use Bdf\Prime\Query\Contract\Query\InsertQueryInterface;
use Bdf\Prime\Query\Contract\Query\KeyValueQueryInterface;
use Bdf\Prime\Query\Contract\WriteOperation;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Prime\Repository\Event\AfterDelete;
use Bdf\Prime\Repository\Event\AfterInsert;
use Bdf\Prime\Repository\Event\AfterUpdate;
use Bdf\Prime\Repository\Event\BeforeDelete;
use Bdf\Prime\Repository\Event\BeforeInsert;
use Bdf\Prime\Repository\Event\BeforeUpdate;
use Bdf\Prime\Repository\RepositoryEventsSubscriberInterface;
use Bdf\Prime\Repository\RepositoryInterface;
use Bdf\Prime\ServiceLocator;
use LogicException;

/**
 * Base implementation of repository writer
 *
 * @template E as object
 * @implements WriterInterface<E>
 */
final class Writer implements WriterInterface
{
    /**
     * @var RepositoryInterface<E>&RepositoryEventsSubscriberInterface<E>
     */
    private RepositoryInterface $repository;

    private ServiceLocator $serviceLocator;

    //==================
    // Prepared queries
    //==================

    private ?InsertQueryInterface $insertQuery = null;
    private KeyValueQueryInterface|QueryInterface|null $deleteQuery = null;
    private KeyValueQueryInterface|QueryInterface|null $updateQuery = null;


    /**
     * Writer constructor.
     *
     * @param RepositoryEventsSubscriberInterface<E>&RepositoryInterface<E> $repository
     * @param ServiceLocator $serviceLocator
     */
    public function __construct(RepositoryInterface $repository, ServiceLocator $serviceLocator)
    {
        $this->repository = $repository;
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function insert(object $entity, array $options = []): int
    {
        /** @var EntityRepository<E> $repository */
        $repository = $this->repository;

        if ($repository->isReadOnly()) {
            throw new LogicException('Repository "'.$repository->entityName().'" is read only. Cannot execute write query');
        }

        if ($repository->notify(new BeforeInsert($entity, $repository)) === false) {
            return 0;
        }

        $data = $repository->mapper()->prepareToRepository($entity);
        $generator = $repository->mapper()->generator();
        $generator->setCurrentConnection($repository->connection());
        $generator->generate($data, $this->serviceLocator);

        $count = $this->insertQuery()->ignore(!empty($options['ignore']))->values($data)->execute()->count();

        $generator->postProcess($entity);

        $repository->notify(new AfterInsert($entity, $this->repository, $count));

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function update(object $entity, array $options = []): int
    {
        if ($this->repository->isReadOnly()) {
            throw new LogicException('Repository "'.$this->repository->entityName().'" is read only. Cannot execute write query');
        }

        /** @var EntityRepository<E> $this->repository */
        $attributes = isset($options['attributes']) ? new \ArrayObject($options['attributes']) : null;

        if ($this->repository->notify(new BeforeUpdate($entity, $this->repository, $attributes)) === false) {
            return 0;
        }

        $data = array_diff_key(
            $this->repository->mapper()->prepareToRepository($entity, $attributes ? array_flip($attributes->getArrayCopy()) : null),
            array_flip($this->repository->mapper()->metadata()->primary['attributes'])
        );

        if (!$data) {
            return 0;
        }

        $count = $this->updateQuery()
            ->where($this->repository->mapper()->primaryCriteria($entity))
            ->values($data)
            ->update()
        ;

        $this->repository->notify(new AfterUpdate($entity, $this->repository, $count));

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function delete(object $entity, array $options = []): int
    {
        if ($this->repository->isReadOnly()) {
            throw new LogicException('Repository "'.$this->repository->entityName().'" is read only. Cannot execute write query');
        }

        /** @var EntityRepository<E> $this->repository */
        if ($this->repository->notify(new BeforeDelete($entity, $this->repository)) === false) {
            return 0;
        }

        $count = $this->deleteQuery()->where($this->repository->mapper()->primaryCriteria($entity))->delete();

        $this->repository->notify(new AfterDelete($entity, $this->repository, $count));

        return $count;
    }

    /**
     * Create the insert query
     *
     * @return InsertQueryInterface
     */
    private function insertQuery(): InsertQueryInterface
    {
        if ($this->insertQuery) {
            return $this->insertQuery;
        }

        $this->insertQuery = $this->repository->queries()->make(InsertQueryInterface::class);
        $this->insertQuery->columns(array_keys($this->repository->mapper()->metadata()->attributes));

        return $this->insertQuery;
    }

    /**
     * Create the delete query
     *
     * @return KeyValueQueryInterface|QueryInterface
     */
    private function deleteQuery(): KeyValueQueryInterface|QueryInterface
    {
        if ($this->deleteQuery) {
            return $this->deleteQuery;
        }

        $this->deleteQuery = $this->repository->queries()->keyValue();

        return $this->deleteQuery ?: $this->repository->queries()->builder();
    }

    /**
     * Create the update query
     *
     * @return KeyValueQueryInterface|QueryInterface
     */
    private function updateQuery(): KeyValueQueryInterface|QueryInterface
    {
        if ($this->updateQuery) {
            return $this->updateQuery;
        }

        $this->updateQuery = $this->repository->queries()->keyValue();

        return $this->updateQuery ?: $this->repository->queries()->builder();
    }
}
