<?php

namespace Bdf\Prime\Query;

use Bdf\Prime\Collection\CollectionFactory;
use Bdf\Prime\Collection\CollectionInterface;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\Result\ResultSetInterface;
use Bdf\Prime\Query\Compiler\CompilerState;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;
use Bdf\Prime\Query\Contract\Projectionable;
use Bdf\Prime\Query\Extension\CachableTrait;
use Bdf\Prime\Query\Extension\ExecutableTrait;
use Bdf\Prime\Record\RecordHydratorInterface;
use Bdf\Prime\Record\SimpleRecordHydrator;

/**
 * Abstract class for read operations
 *
 * @template C as ConnectionInterface
 * @template R as object|array
 *
 * @implements ReadCommandInterface<C, R>
 * @psalm-no-seal-methods
 */
abstract class AbstractReadCommand extends CompilableClause implements ReadCommandInterface
{
    use CachableTrait;
    use ExecutableTrait;

    /**
     * The DBAL Connection.
     *
     * @var C
     */
    protected $connection;

    /**
     * The collection class name that wrap query result
     *
     * @var string
     */
    protected $wrapper;

    /**
     * The listeners processor.
     *
     * @var array{
     *    post: callable(\Bdf\Prime\Connection\Result\ResultSetInterface<array<string, mixed>>):array|null,
     *    each: callable(array<string, mixed>):mixed|null
     * }
     */
    protected $listeners = [
        'post' => null,
        'each' => null,
    ];

    /**
     * The SQL compiler
     *
     * @var object
     */
    protected $compiler;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var object
     */
    protected $extension;

    protected ?string $recordClassName = null;
    protected ?RecordHydratorInterface $recordHydrator = null;


    /**
     * AbstractReadCommand constructor.
     *
     * @param C $connection
     * @param PreprocessorInterface $preprocessor
     */
    public function __construct(ConnectionInterface $connection, PreprocessorInterface $preprocessor)
    {
        parent::__construct($preprocessor, new CompilerState());

        $this->on($connection);
    }

    /**
     * {@inheritdoc}
     */
    public function compiler(): object
    {
        return $this->compiler;
    }

    /**
     * {@inheritdoc}
     */
    public function collectionFactory(): CollectionFactory
    {
        if ($this->collectionFactory === null) {
            $this->collectionFactory = CollectionFactory::forDbal();
        }

        return $this->collectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function setCollectionFactory(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function connection(): ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function on(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        /** @psalm-suppress InvalidArgument */
        $this->compiler = $connection->factory()->compiler(get_class($this));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function post(callable $processor, bool $forEach = true)
    {
        $this->listeners[$forEach ? 'each' : 'post'] = $processor;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function wrapAs(string $wrapperClass)
    {
        $this->wrapper = $wrapperClass;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setRecordHydrator(RecordHydratorInterface $hydrator): void
    {
        $this->recordHydrator = $hydrator;
    }

    /**
     * Get the current record hydrator, and create {@see SimpleRecordHydrator} if not set
     */
    protected function recordHydrator(): RecordHydratorInterface
    {
        return ($this->recordHydrator ??= SimpleRecordHydrator::instance());
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress InvalidReturnStatement
     */
    public function as(string $recordClassName)
    {
        $this->recordClassName = $recordClassName;

        $projection = $this->recordHydrator()->projection($recordClassName);

        if ($projection !== null && $this instanceof Projectionable) {
            $this->project($projection);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return array|CollectionInterface
     */
    public function postProcessResult(ResultSetInterface $data): iterable
    {
        if ($this->listeners['post'] !== null) {
            $proceed = $this->listeners['post']($data);
        } elseif (($listener = $this->listeners['each']) !== null) {
            $proceed = [];

            foreach ($data as $row) {
                $proceed[] = $listener($row);
            }
        } else {
            $proceed = $data->all();
        }

        if ($recordClassName = $this->recordClassName) {
            $hydrated = [];
            $recordManager = $this->recordHydrator();
            $platform = $this->connection->platform();

            foreach ($recordManager->prepare($recordClassName, $proceed) as $row) {
                $hydrated[] = $recordManager->instantiate($recordClassName, $row, $platform);
            }

            $hydrated = $recordManager->finalize($recordClassName, $hydrated);
        } else {
            $hydrated = $proceed;
        }

        if ($this->wrapper !== null) {
            return $this->collectionFactory()->wrap($hydrated, $this->wrapper);
        }

        return $hydrated;
    }

    /**
     * {@inheritdoc}
     */
    public function setExtension($extension): void
    {
        $this->extension = $extension;
    }

    /**
     * Extension call
     * run the query extension, set by the builder
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->extension->$name($this, ...$arguments);
    }
}
