<?php

namespace Bdf\Prime\Repository\Write;

use Bdf\Event\EventNotifier;
use Bdf\Prime\Events;
use Bdf\Prime\Query\Contract\Query\InsertQueryInterface;
use Bdf\Prime\Query\Contract\WriteOperation;
use Bdf\Prime\Query\Custom\KeyValue\KeyValueQuery;
use Bdf\Prime\Repository\RepositoryInterface;
use Bdf\Prime\ServiceLocator;

/**
 * Base implementation of repository writer
 */
class Writer implements WriterInterface
{
    /**
     * @var RepositoryInterface|EventNotifier
     */
    private $repository;

    /**
     * @var ServiceLocator
     */
    private $serviceLocator;

    //==================
    // Prepared queries
    //==================

    /**
     * @var InsertQueryInterface
     */
    private $insertQuery;

    /**
     * @var KeyValueQuery
     */
    private $deleteQuery;

    /**
     * @var KeyValueQuery
     */
    private $updateQuery;


    /**
     * Writer constructor.
     *
     * @param EventNotifier|RepositoryInterface $repository
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
    public function insert($entity, array $options = [])
    {
        if ($this->repository->notify(Events::PRE_INSERT, [$entity, $this->repository]) === false) {
            return 0;
        }

        $data = $this->repository->mapper()->prepareToRepository($entity);
        $generator = $this->repository->mapper()->generator();
        $generator->setCurrentConnection($this->repository->connection());
        $generator->generate($data, $this->serviceLocator);

        $count = $this->insertQuery()->ignore(!empty($options['ignore']))->values($data)->execute();

        $generator->postProcess($entity);

        $this->repository->notify(Events::POST_INSERT, [$entity, $this->repository, $count]);

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function update($entity, array $options = [])
    {
        $attributes = isset($options['attributes']) ? new \ArrayObject($options['attributes']) : null;

        if ($this->repository->notify(Events::PRE_UPDATE, [$entity, $this->repository, $attributes]) === false) {
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

        $this->repository->notify(Events::POST_UPDATE, [$entity, $this->repository, $count]);

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function delete($entity, array $options = [])
    {
        if ($this->repository->notify(Events::PRE_DELETE, [$entity, $this->repository]) === false) {
            return 0;
        }

        $count = $this->deleteQuery()->where($this->repository->mapper()->primaryCriteria($entity))->delete();

        $this->repository->notify(Events::POST_DELETE, [$entity, $this->repository, $count]);

        return $count;
    }

    /**
     * Create the insert query
     *
     * @return InsertQueryInterface
     */
    private function insertQuery()
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
     * @return KeyValueQuery
     */
    private function deleteQuery()
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
     * @return KeyValueQuery
     */
    private function updateQuery()
    {
        if ($this->updateQuery) {
            return $this->updateQuery;
        }

        $this->updateQuery = $this->repository->queries()->keyValue();

        return $this->updateQuery ?: $this->repository->queries()->builder();
    }
}
