<?php

namespace Bdf\Prime\Collection;

use Bdf\Prime\Repository\RepositoryInterface;

/**
 * Factory for CollectionInterface
 */
class CollectionFactory
{
    /**
     * Alias of known collection class
     *
     * @var array<string, class-string<CollectionInterface>|callable(array):CollectionInterface>
     */
    private $aliases = [
        'array' => ArrayCollection::class,
    ];

    /**
     * List of collection factories, indexed by the class name
     *
     * @var array<class-string<CollectionInterface>, callable(array):CollectionInterface>
     */
    private $factories = [];


    /**
     * Cannot instantiate externally
     */
    protected function __construct() {}

    /**
     * Register a new wrapper alias for @see Query::wrapAs()
     *
     * <pre><code>
     * $factory->registerWrapperAlias('myCollection', MyCollection::class);
     * $factory->wrap($data, 'myCollection'); //Will perform `new MyCollection($data);`
     * $factory->registerWrapperAlias('myCollection', function ($data) {
     *      $collection = new MyCollection($data);
     *      $collection->doSomething();
     *      return $collection;
     * });
     * $factory->wrap($data, 'myCollection'); //Will perform `$closure($data);`
     * $factory->registerWrapperAlias('myCollection', MyCollection::class, function ($data) {
     *     return new MyCollection(...);
     * });
     * $factory->wrap($data, 'myCollection'); //Will perform `$closure($data);`
     * $factory->wrap($data, MyCollection::class);
     * </code></pre>
     *
     * @param string $wrapperAlias The wrapper alias name
     * @param class-string<CollectionInterface>|callable(array):CollectionInterface $wrapperClass The wrapper class, or callable for instantiate the wrapper
     * @param callable(array):CollectionInterface|null $factory The wrapper factory. If null use the constructor
     *
     * @return void
     */
    public function registerWrapperAlias(string $wrapperAlias, $wrapperClass, ?callable $factory = null)
    {
        $this->aliases[$wrapperAlias] = $wrapperClass;

        if ($factory) {
            /** @var class-string<CollectionInterface> $wrapperClass */
            $this->factories[$wrapperClass] = $factory;
        }
    }

    /**
     * Wrap data with a wrapper class
     *
     * @param array $data
     * @param string|callable $wrapper
     *
     * @return CollectionInterface
     */
    public function wrap(array $data, $wrapper = 'array')
    {
        if (is_string($wrapper) && isset($this->aliases[$wrapper])) {
            $wrapper = $this->aliases[$wrapper];
        }

        if (is_string($wrapper)) {
            if (isset($this->factories[$wrapper])) {
                return $this->factories[$wrapper]($data);
            } else {
                return new $wrapper($data);
            }
        }

        return $wrapper($data);
    }

    /**
     * Get the wrapper class from a wrapper alias
     *
     * @param string $wrapper
     *
     * @return class-string
     */
    public function wrapperClass(string $wrapper): string
    {
        if (isset($this->aliases[$wrapper])) {
            $wrapper = $this->aliases[$wrapper];
        }

        if (!is_string($wrapper) || !class_exists($wrapper)) {
            throw new \InvalidArgumentException('');
        }

        return $wrapper;
    }

    /**
     * Create a CollectionFactory related to an EntityRepository
     *
     * @param RepositoryInterface $repository
     *
     * @return static
     */
    public static function forRepository(RepositoryInterface $repository): self
    {
        $factory = new static();
        $factory->registerWrapperAlias('collection', EntityCollection::class, [$repository, 'collection']);

        return $factory;
    }

    /**
     * Create a CollectionFactory for simple Queries
     *
     * /!\ The CollectionFactory instance is shared by ALL queries. You should not overrides wrapper aliases
     *
     * @return static
     */
    public static function forDbal(): self
    {
        static $instance;

        if ($instance === null) {
            $instance = new static();
        }

        return $instance;
    }

    /**
     * Get the handled collection classes
     *
     * @return string[]
     */
    public static function collections(): array
    {
        return [ArrayCollection::class, EntityCollection::class];
    }
}
