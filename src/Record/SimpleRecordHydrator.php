<?php

namespace Bdf\Prime\Record;

use Bdf\Prime\Platform\PlatformInterface;

/**
 * Base record hydrator for DBAL queries
 */
final class SimpleRecordHydrator implements RecordHydratorInterface
{
    /**
     * Cache record instantiators
     *
     * The key is the record class name
     * The value is the instantiator instance
     *
     * @var array<class-string, RecordInstantiator>
     * @psalm-var class-string-map<R, RecordInstantiator<R>>
     */
    private array $cache = [];

    /**
     * {@inheritdoc}
     */
    public function projection(string $recordClass): ?array
    {
        return $this->instantiator($recordClass)->projection();
    }

    /**
     * {@inheritdoc}
     */
    public function prepare(string $recordClass, array $rows): array
    {
        return $rows;
    }

    /**
     * {@inheritdoc}
     */
    public function instantiate(string $recordClass, array $data, PlatformInterface $platform): object
    {
        return $this->instantiator($recordClass)->instantiate($data, $platform);
    }

    /**
     * {@inheritdoc}
     */
    public function finalize(string $recordClass, array $entities, array $rows): array
    {
        return $entities;
    }

    /**
     * @param class-string<T> $recordClass
     * @return RecordInstantiator<T>
     * @template T as object
     */
    private function instantiator(string $recordClass): RecordInstantiator
    {
        return $this->cache[$recordClass] ??= RecordInstantiator::fromRecordClass($recordClass);
    }

    /**
     * Get or create the instance of the manager
     */
    public static function instance(): self
    {
        static $instance;

        return $instance ??= new self();
    }
}
