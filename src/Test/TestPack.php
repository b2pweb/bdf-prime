<?php

namespace Bdf\Prime\Test;

use Bdf\Event\EventNotifier;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Connection\SimpleConnection;
use Bdf\Prime\Prime;
use Bdf\Prime\Repository\EntityRepository;

/**
 * TestPack
 *
 * @author  Seb
 * @author  Gilles Gautier
 * @author  Virginie
 */
class TestPack
{
    use EventNotifier;

    /**
     * Singleton
     *
     * @var static|null
     */
    protected static $pack;

    /**
     * Test data by group
     *
     * @var array{persistent:object[], non-persistent: object[]}
     */
    protected $testPacks = [
        'persistent'        => [],  // Entités créées non modifiables
        'non-persistent'    => []   // Entités créées et détruites aprés un test
    ];

    /**
     * @var bool
     */
    private $initialized = false;

    /**
     * @var class-string[]
     */
    private $entityClasses = [];

    /**
     * @var array<string, object>
     */
    private $entities = [];


    /**
     * Singleton
     *
     * @return self
     */
    public static function pack()
    {
        if (static::$pack === null) {
            static::$pack = new static;
        }

        return static::$pack;
    }

    /**
     * Check whether the pack is initialized
     *
     * @return bool Return true if testPack is initialized
     */
    public function isInitialized()
    {
        return $this->initialized;
    }

    /**
     * Declare persistent data
     *
     * @param object|array $entities
     *
     * @return $this
     */
    public function persist($entities)
    {
        if ($this->initialized) {
            return $this;
        }

        return $this->storeEntities($entities, 'persistent');
    }

    /**
     * Set non persitent test pack entities
     *
     * Entities could be:
     *  - an entity object
     *  - a collection of entities
     *  - a collection of entities with alias as key
     *
     * @param object|array $entities
     *
     * @return $this
     * @psalm-assert true $this->initialized
     */
    public function nonPersist($entities)
    {
        if (!$this->initialized) {
            throw new \LogicException('Non persistent data cannot be declared before initialization');
        }

        return $this->storeEntities($entities, 'non-persistent');
    }

    /**
     * Store entities
     *
     * @param object|object[] $entities
     *
     * @return $this
     */
    protected function storeEntities($entities, $mode)
    {
        if (!is_array($entities)) {
            $entities = [$entities];
        }

        foreach ($entities as $alias => $entity) {
            $this->declareEntity(get_class($entity));

            if (is_string($alias)) {
                $this->testPacks[$mode][$alias] = $entity;
                $this->entities[$alias] = $entity;
            } else {
                $this->testPacks[$mode][] = $entity;
            }

            if ($this->initialized) {
                $this->pushEntity($entity);
            }
        }

        return $this;
    }

    /**
     * Declare a entity class for schema managing
     *
     * @param class-string|list<class-string> $entityClasses
     *
     * @return self
     */
    public function declareEntity($entityClasses)
    {
        if (!is_array($entityClasses)) {
            $entityClasses = [$entityClasses];
        }

        // On ajoute la classe de l'entité uniquement si elle n'est pas déjà dans le tableau et si le test pack n'a pas démarré de savepoint
        foreach ($entityClasses as $entityClassName) {
            if (! in_array($entityClassName, $this->entityClasses)) {
                if ($this->initialized) {
                    $this->create([$entityClassName]);
                } else {
                    $this->entityClasses[] = $entityClassName;
                }
            }
        }

        return $this;
    }

    /**
     * Initialize schema and data
     *
     * @return self
     * @psalm-assert true $this->initialized
     */
    public function initialize()
    {
        if ($this->initialized === false) {
            $this->create($this->entityClasses);

            foreach ($this->testPacks['persistent'] as $entity) {
                $this->pushEntity($entity);
            }

            $this->initialized = true;

            $this->notify('testpack.initialized');
        }

        $this->createSavePoint();

        return $this;
    }

    /**
     * Create schema
     *
     * @param list<class-string> $entityClasses
     *
     * @return $this
     */
    protected function create(array $entityClasses)
    {
        Prime::create($entityClasses, true);

        return $this;
    }

    /**
     * Register callback on initialized event
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function onInit(callable $callback)
    {
        if (! $this->initialized) {
            $this->once('testpack.initialized', $callback);
        }

        return $this;
    }

    /**
     * Drop schema and reset test pack
     *
     * @return $this
     */
    public function destroy()
    {
        $this->rollbackToSavePoint();

        Prime::drop($this->entityClasses, true);

        $this->initialized = false;
        $this->entities = [];
        $this->entityClasses = [];
        $this->testPacks = [
            'persistent'        => [],
            'non-persistent'    => [],
        ];

        $this->notify('testpack.destroyed');

        return $this;
    }

    /**
     * Register callback on destroy event
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function onDestroy(callable $callback)
    {
        $this->once('testpack.destroyed', $callback);

        return $this;
    }

    /**
     * Clear non-persistent user definition
     *
     * @return $this
     */
    public function clear()
    {
        foreach ($this->testPacks['non-persistent'] as $alias => $entity) {
            unset($this->entities[$alias]);
        }

        $this->testPacks['non-persistent'] = [];

        $this->rollbackToSavePoint();

        return $this;
    }

    /**
     * Get an entity by alias from test pack
     *
     * @param string $name        The entity alias name
     *
     * @return object|null
     */
    public function get($name)
    {
        if (isset($this->entities[$name])) {
            return $this->entities[$name];
        }

        return null;
    }

    /**
     * Push entity to repository
     *
     * @param object $entity
     */
    public function pushEntity($entity)
    {
        /** @var EntityRepository $repository */
        $repository = Prime::repository($entity);
        $mapper = $repository->mapper();

        $isReadOnly = $mapper->isReadOnly();
        $mapper->setReadOnly(false);

        $repository->disableEventNotifier();
        $repository->insert($entity);
        $repository->enableEventNotifier();

        $mapper->setReadOnly($isReadOnly);
    }

    /**
     * Delete entity from repository
     *
     * @param object $entity
     */
    public function deleteEntity($entity)
    {
        /** @var EntityRepository $repository */
        $repository = Prime::repository($entity);
        $mapper = $repository->mapper();

        $isReadOnly = $mapper->isReadOnly();
        $mapper->setReadOnly(false);

        $repository->disableEventNotifier();
        $repository->delete($entity);
        $repository->enableEventNotifier();

        $mapper->setReadOnly($isReadOnly);
    }

    /**
     * Create a save point on each active connections
     */
    protected function createSavePoint()
    {
        foreach ($this->getActiveConnections() as $connection) {
            try {
                $connection->setNestTransactionsWithSavepoints(true);
                $connection->beginTransaction();
            } catch (\Exception $e) {

            }
        }
    }

    /**
     * Remove a save point on each active connections
     */
    protected function rollbackToSavePoint()
    {
        foreach ($this->getActiveConnections() as $connection) {
            try {
                while ($connection->isTransactionActive()) {
                    $connection->rollBack();
                }
            } catch (\Exception $e) {

            }
        }
    }

    /**
     * Get Prime connections
     *
     * @return SimpleConnection[]
     */
    private function getActiveConnections()
    {
        /** @var SimpleConnection[] */
        return Prime::service()->connections()->connections();
    }
}
