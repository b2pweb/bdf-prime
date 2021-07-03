<?php

namespace Bdf\Prime\Collection\Indexer;

use Bdf\Prime\Mapper\Mapper;

/**
 * Entity indexer for singleton entity
 *
 * @template E as object
 * @implements EntityIndexerInterface<E>
 */
final class SingleEntityIndexer implements EntityIndexerInterface
{
    /**
     * @var Mapper<E>
     */
    private $mapper;

    /**
     * @var E
     */
    private $entity;


    /**
     * SingleEntityIndexer constructor.
     *
     * @param Mapper<E> $mapper
     * @param E $entity
     */
    public function __construct(Mapper $mapper, $entity)
    {
        $this->mapper = $mapper;
        $this->entity = $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function by(string $key): array
    {
        return [$this->mapper->extractOne($this->entity, $key) => [$this->entity]];
    }

    /**
     * {@inheritdoc}
     */
    public function byOverride(string $key): array
    {
        return [$this->mapper->extractOne($this->entity, $key) => $this->entity];
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return [$this->entity];
    }

    /**
     * {@inheritdoc}
     */
    public function empty(): bool
    {
        return false;
    }
}
