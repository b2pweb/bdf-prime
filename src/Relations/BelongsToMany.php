<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Contract\EntityJoinable;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Query\Contract\WriteOperation;
use Bdf\Prime\Query\Custom\KeyValue\KeyValueQuery;
use Bdf\Prime\Query\QueryInterface;
use Bdf\Prime\Query\ReadCommandInterface;
use Bdf\Prime\Repository\EntityRepository;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * BelongsToMany
 *
 * For a relation named 'relation' use the prefix 'relationThrough.' for adding constraints on through table.
 * ex:
 *
 * <code>
 * $query->with([
 *     'relation' => [
 *         'name :like'             => '...',
 *         'relationThrough.status' => '...'  // through constraint
 *     ]
 * ]);
 * </code>
 *
 * @package Bdf\Prime\Relations
 *
 * @todo Voir pour gérer la table de through dynamiquement. Si cette relation est une HasManyThrough, elle doit etre en readonly
 */
class BelongsToMany extends Relation
{
    /**
     * Through repository
     *
     * @var EntityRepository
     */
    protected $through;

    /**
     * Through local key
     *
     * @var string
     */
    protected $throughLocal;

    /**
     * Through distant key
     *
     * @var string
     */
    protected $throughDistant;

    /**
     * The through global constraints
     *
     * @var array
     */
    protected $throughConstraints = [];

    /**
     * Merge of all constraints
     *
     * @var array
     */
    protected $allConstraints = [];
    
    /**
     * {@inheritdoc}
     */
    protected $saveStrategy = self::SAVE_STRATEGY_REPLACE;

    //===============================
    // Save queries for optimisation
    //===============================

    /**
     * @var KeyValueQuery
     */
    private $throughQuery;

    /**
     * @var KeyValueQuery
     */
    private $relationQuery;


    /**
     * {@inheritdoc}
     */
    public function relationRepository(): RepositoryInterface
    {
        return $this->distant;
    }

    /**
     * Set the though infos
     *
     * @param RepositoryInterface $through
     * @param string $throughLocal
     * @param string $throughDistant
     */
    public function setThrough(RepositoryInterface $through, string $throughLocal, string $throughDistant)
    {
        $this->through        = $through;
        $this->throughLocal   = $throughLocal;
        $this->throughDistant = $throughDistant;
    }

    /**
     * {@inheritdoc}
     */
    public function setConstraints($constraints)
    {
        $this->allConstraints = $constraints;

        list($this->constraints, $this->throughConstraints) = $this->extractConstraints($constraints);

        return $this;
    }

    /**
     * Extract constraints design for through queries
     *
     * @param array|\Closure $constraints
     *
     * @return array
     */
    protected function extractConstraints($constraints)
    {
        if (!is_array($constraints)) {
            return [$constraints, []];
        }

        $through = [];
        $global = [];
        $prefix = $this->attributeAim.'Through.';
        $length = strlen($prefix);

        foreach ($constraints as $column => $value) {
            if (strpos($column, $prefix) === 0) {
                $through[substr($column, $length)] = $value;
            } else {
                $global[$column] = $value;
            }
        }

        return [$global, $through];
    }

    /**
     * {@inheritdoc}
     */
    public function join(EntityJoinable $query, string $alias): void
    {
        // @fixme ??
//        if ($alias === null) {
//            $alias = $this->attributeAim;
//        }

        // TODO rechercher l'alias de through dans les tables alias du query builder

        $query->joinEntity($this->through->entityName(), $this->throughLocal, $this->getLocalAlias($query).$this->localKey, $this->attributeAim.'Through');
        $query->joinEntity($this->distant->entityName(), $this->distantKey, $this->attributeAim.'Through>'.$this->throughDistant, $alias);

        $this->applyConstraints($query, [], $alias);
        $this->applyThroughConstraints($query, [], $this->attributeAim.'Through');
    }

    /**
     * {@inheritdoc}
     */
    public function joinRepositories(EntityJoinable $query, string $alias, $discriminator = null): array
    {
        return [
            $this->attributeAim.'Through' => $this->through,
            $alias => $this->distant,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function link($owner): ReadCommandInterface
    {
        return $this->distant->queries()->builder()
            ->joinEntity($this->through->entityName(), $this->throughDistant, $this->distantKey, $this->attributeAim.'Through')
            ->where($this->attributeAim.'Through.'.$this->throughLocal, $this->getLocalKeyValue($owner))
            ->where($this->allConstraints);
    }

    /**
     * Get a query from through entity repository
     *
     * @param string|array  $key
     * @param array $constraints
     *
     * @return ReadCommandInterface
     */
    protected function throughQuery($key, $constraints = []): ReadCommandInterface
    {
        if (is_array($key)) {
            if (count($key) !== 1 || $constraints || $this->throughConstraints) {
                return $this->applyThroughConstraints(
                    $this->through->where($this->throughLocal, $key),
                    $constraints
                );
            }

            $key = $key[0];
        }

        if ($this->throughQuery) {
            return $this->throughQuery->where($this->throughLocal, $key);
        }

        $this->throughQuery = $this->through->queries()->keyValue($this->throughLocal, $key);

        if ($this->throughQuery) {
            return $this->throughQuery;
        }

        return $this->applyThroughConstraints(
            $this->through->where($this->throughLocal, $key),
            $constraints
        );
    }

    /**
     * Build the query for find related entities
     */
    protected function relationQuery($keys, $constraints): ReadCommandInterface
    {
        // Constraints can be on relation attributes : builder must be used
        // @todo Handle "bulk select"
        if (count($keys) !== 1 || $constraints || $this->constraints) {
            return $this->query($keys, $constraints)->by($this->distantKey);
        }

        if ($this->relationQuery) {
            return $this->relationQuery->where($this->distantKey, reset($keys));
        }

        $query = $this->distant->queries()->keyValue($this->distantKey, reset($keys));

        if (!$query) {
            return $this->query($keys, $constraints)->by($this->distantKey);
        }

        return $this->relationQuery = $query->by($this->distantKey);
    }

    /**
     * Apply the through constraints
     *
     * @param Q $query
     * @param array $constraints
     * @param string|null $context
     *
     * @return Q
     *
     * @template Q as ReadCommandInterface&\Bdf\Prime\Query\Contract\Whereable
     */
    protected function applyThroughConstraints(ReadCommandInterface $query, $constraints = [], ?string $context = null): ReadCommandInterface
    {
        return $query->where($this->applyContext($context, $constraints + $this->throughConstraints));
    }

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    protected function relations($keys, $with, $constraints, $without): array
    {
        list($constraints, $throughConstraints) = $this->extractConstraints($constraints);

        $throughEntities = [];
        $throughDistants = [];

        $collection = $this->throughQuery($keys, $throughConstraints)->execute([
            $this->throughLocal   => $this->throughLocal,
            $this->throughDistant => $this->throughDistant,
        ]);

        foreach ($collection as $entity) {
            $throughLocal   = $entity[$this->throughLocal];
            $throughDistant = $entity[$this->throughDistant];

            $throughDistants[$throughDistant] = $throughDistant;
            $throughEntities[$throughLocal][$throughDistant] = $throughDistant;
        }

        $relations = $this->relationQuery($throughDistants, $constraints)
            ->with($with)
            ->without($without)
            ->all();

        return [
            'throughEntities' => $throughEntities,
            'entities'        => $relations,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function match($collection, $relations): void
    {
        foreach ($relations['throughEntities'] as $key => $throughDistants) {
            $entities = [];

            foreach ($throughDistants as $throughDistant) {
                if (isset($relations['entities'][$throughDistant])) {
                    $entities[] = $relations['entities'][$throughDistant];
                }
            }

            if (empty($entities)) {
                continue;
            }

            foreach ($collection[$key] as $local) {
                $this->setRelation($local, $entities);
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws PrimeException
     */
    #[WriteOperation]
    public function associate($owner, $entity)
    {
        $this->attach($owner, $entity);

        return $owner;
    }

    /**
     * {@inheritdoc}
     *
     * @throws PrimeException
     */
    #[WriteOperation]
    public function dissociate($owner)
    {
        $this->detach($owner, $this->getRelation($owner));

        return $owner;
    }

    /**
     * {@inheritdoc}
     *
     * @throws PrimeException
     */
    public function create($owner, array $data = [])
    {
        $entity = $this->distant->entity($data);

        $this->distant->save($entity);

        $this->add($owner, $entity);

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function add($owner, $related)
    {
        return $this->attach($owner, $related);
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function saveAll($owner, array $relations = []): int
    {
        //Detach all relations
        if ($this->saveStrategy === self::SAVE_STRATEGY_REPLACE) {
            $this->throughQuery($this->getLocalKeyValue($owner))->delete();
        }
        
        // Attach new relations
        return $this->attach($owner, $this->getRelation($owner));
    }

    /**
     * {@inheritdoc}
     */
    #[WriteOperation]
    public function deleteAll($owner, array $relations = []): int
    {
        return $this->detach($owner, $this->getRelation($owner));
    }

    /**
     * Check whether the owner has a distant entity relation
     *
     * @param object          $owner
     * @param string|object   $entity
     *
     * @return boolean
     * @throws PrimeException
     */
    #[ReadOperation]
    public function has($owner, $entity): bool
    {
        $data = [$this->throughLocal => $this->getLocalKeyValue($owner)];

        if (!is_object($entity)) {
            $data[$this->throughDistant] = $entity;
        } else {
            $data[$this->throughDistant] = $this->getDistantKeyValue($entity);
        }

        return $this->through->exists($this->through->entity($data));
    }

    /**
     * Attach a distant entity to an entity
     *
     * @param object                    $owner
     * @param string|array|object       $entities
     *
     * @return int
     * @throws PrimeException
     */
    #[WriteOperation]
    public function attach($owner, $entities): int
    {
        if (empty($entities)) {
            return 0;
        }

        $ownerId = $this->getLocalKeyValue($owner);

        if (!is_array($entities)) {
            $entities = [$entities];
        }

        $nb = 0;

        foreach ($entities as $entity) {
            // distant could be a object or the distant id
            $data = [$this->throughLocal => $ownerId];

            if (!is_object($entity)) {
                $data[$this->throughDistant] = $entity;
            } else {
                $data[$this->throughDistant] = $this->getDistantKeyValue($entity);
            }

            $nb += $this->through->save($this->through->entity($data));
        }

        return $nb;
    }

    /**
     * Detach a distant entity of an entity
     *
     * @param object                $owner
     * @param string|array|object   $entities
     *
     * @return int
     * @throws PrimeException
     */
    #[WriteOperation]
    public function detach($owner, $entities): int
    {
        if (empty($entities)) {
            return 0;
        }

        $ownerId = $this->getLocalKeyValue($owner);

        if (!is_array($entities)) {
            $entities = [$entities];
        }

        $nb = 0;

        foreach ($entities as $entity) {
            // distant could be a object or the distant id
            $data = [$this->throughLocal => $ownerId];

            if (!is_object($entity)) {
                $data[$this->throughDistant] = $entity;
            } else {
                $data[$this->throughDistant] = $this->getDistantKeyValue($entity);
            }

            $nb += $this->through->delete($this->through->entity($data));
        }

        return $nb;
    }
}
