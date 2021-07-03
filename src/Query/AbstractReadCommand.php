<?php

namespace Bdf\Prime\Query;

use Bdf\Prime\Collection\CollectionFactory;
use Bdf\Prime\Collection\CollectionInterface;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Compiler\CompilerState;
use Bdf\Prime\Query\Compiler\Preprocessor\PreprocessorInterface;
use Bdf\Prime\Query\Extension\CachableTrait;
use Bdf\Prime\Query\Extension\ExecutableTrait;

/**
 * Abstract class for read operations
 *
 * @template C as ConnectionInterface
 * @template R as object|array
 *
 * @implements ReadCommandInterface<C, R>
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
     * @var array
     */
    protected $listeners = [
        'post' => null,
        'each' => null,
    ];

    /**
     * The SQL compiler
     *
     * @var CompilerInterface
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
    public function compiler(): CompilerInterface
    {
        return $this->compiler;
    }

    /**
     * {@inheritdoc}
     */
    public function setCompiler(CompilerInterface $compiler)
    {
        $this->compiler = $compiler;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function collectionFactory()
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
        $this->compiler = $connection->factory()->compiler(get_class($this));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function post(callable $processor, $forEach = true)
    {
        $this->listeners[$forEach ? 'each' : 'post'] = $processor;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function wrapAs($wrapperClass)
    {
        $this->wrapper = $wrapperClass;

        return $this;
    }

    /**
     * Post processors.
     * Wrap data with defined wrapper. Run the post processors on rows
     *
     * @param array  $data
     *
     * @return array|CollectionInterface
     */
    public function postProcessResult($data)
    {
        if ($this->listeners['post'] !== null) {
            $data = $this->listeners['post']($data);
        } elseif ($this->listeners['each'] !== null) {
            $data = array_map($this->listeners['each'], $data);
        }

        if ($this->wrapper !== null) {
            return $this->collectionFactory()->wrap($data, $this->wrapper);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function setExtension($extension)
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
