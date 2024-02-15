<?php

namespace Bdf\Prime\Query\Compiled;

use Bdf\Prime\Cache\CacheInterface;
use Bdf\Prime\Collection\CollectionFactory;
use Bdf\Prime\Collection\CollectionInterface;
use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\Connection\SimpleConnection;
use Bdf\Prime\Query\Extension\CachableTrait;
use Bdf\Prime\Query\Extension\ExecutableTrait;
use Bdf\Prime\Query\QueryRepositoryExtension;
use LogicException;

use function serialize;
use function sha1;

/**
 * Query for constant and compiled SQL query
 *
 * This class is immutable, so it can be reused and shared, and all modifications return a new instance
 *
 * @template R as object|array
 * @implements CompiledQueryInterface<R>
 */
final class CompiledSqlQuery implements CompiledQueryInterface
{
    use ExecutableTrait;
    use CachableTrait {
        useCache as private _useCache;
    }

    private string $query;
    private array $bindings = [];
    private SimpleConnection $connection;
    private ?string $table = null;

    private ?QueryRepositoryExtension $extension = null;
    private ?string $wrapper = null;
    private ?CollectionFactory $collectionFactory = null;

    /**
     * @param SimpleConnection $connection
     * @param string $query
     */
    public function __construct(SimpleConnection $connection, string $query)
    {
        $this->query = $query;
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function setCache(CacheInterface $cache = null)
    {
        $self = clone $this;
        $self->cache = $cache;

        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public function useCache(int $lifetime = 0, ?string $key = null, ?string $namespace = null)
    {
        return (clone $this)->_useCache($lifetime, $key, $namespace);
    }

    /**
     * {@inheritdoc}
     */
    public function withBindings(array $bindings): self
    {
        if ($bindings === $this->bindings) {
            return $this;
        }

        $self = clone $this;
        $self->bindings = $bindings;

        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public function withMetadata(array $metadata): self
    {
        if (!$metadata) {
            return $this;
        }

        $self = clone $this;

        if (isset($metadata['wrapper'])) {
            $self->wrapper = $metadata['wrapper'];
        }

        if (isset($metadata['cache_key'])) {
            $self->_useCache(
                $metadata['cache_key']['lifetime'] ?? 0,
                $metadata['cache_key']['key'] ?? null,
                $metadata['cache_key']['namespace'] ?? null
            );
        }

        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public function withExtensionMetadata(array $metadata): self
    {
        if (!$metadata) {
            return $this;
        }

        if (!$this->extension) {
            throw new LogicException('Extension must be set before calling withExtensionMetadata');
        }

        $self = clone $this;
        $self->extension = clone $this->extension;
        $self->extension->applyMetadata($metadata);

        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public function wrapAs(string $wrapperClass)
    {
        $self = clone $this;
        $self->wrapper = $wrapperClass;

        return $self;
    }

    /**
     * {@inheritdoc}
     *
     * @return array|CollectionInterface
     */
    public function postProcessResult(ResultSetInterface $data): iterable
    {
        if ($extension = $this->extension) {
            $proceed = $extension->processEntities($data);
        } else {
            $proceed = $data->all();
        }

        if ($this->wrapper !== null && $this->collectionFactory) {
            return $this->collectionFactory->wrap($proceed, $this->wrapper);
        }

        return $proceed;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($columns = null): ResultSetInterface
    {
        return $this->executeCached();
    }

    /**
     * @internal
     */
    protected function limit(?int $limit, ?int $offset = null)
    {
        // No-op, use for compatibility with ExecutableTrait
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function compile(bool $forceRecompile = false)
    {
        return $this->query;
    }

    /**
     * {@inheritdoc}
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * {@inheritdoc}
     */
    public function type(): string
    {
        return self::TYPE_SELECT;
    }

    /**
     * {@inheritdoc}
     */
    public function setTable(?string $table): self
    {
        $this->table = $table;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setCollectionFactory(CollectionFactory $collectionFactory): self
    {
        $this->collectionFactory = $collectionFactory;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setExtension(?QueryRepositoryExtension $extension): self
    {
        $this->extension = $extension;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function cacheKey(): ?string
    {
        return sha1($this->query.'-'. serialize($this->bindings));
    }

    /**
     * {@inheritdoc}
     */
    protected function cacheNamespace(): string
    {
        return $this->connection->getName().':'.$this->table;
    }
}
