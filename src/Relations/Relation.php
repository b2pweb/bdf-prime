<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\Collection\Indexer\EntityIndexerInterface;
use Bdf\Prime\Exception\PrimeException;
use Bdf\Prime\Query\Contract\ReadOperation;
use Bdf\Prime\Relations\Util\ForeignKeyRelation;
use Bdf\Prime\Repository\RepositoryInterface;

/**
 * Relation
 *
 * @todo Mettre le saveStrategy en trait + supprimer ou deprécier cette classe
 */
abstract class Relation extends AbstractRelation
{
    use Polymorph;
    use ForeignKeyRelation;

    // save strategies
    const SAVE_STRATEGY_REPLACE = 1;
    const SAVE_STRATEGY_ADD = 2;

    /**
     * The save cascade strategy. See constance SAVE_STRATEGY_*
     *
     * @var int
     */
    protected $saveStrategy;


    /**
     * Set the relation info
     *
     * @param string              $attributeAim  The property name that hold the relation
     * @param RepositoryInterface $local
     * @param string              $localKey
     * @param RepositoryInterface $distant
     * @param string              $distantKey
     */
    public function __construct($attributeAim, RepositoryInterface $local, $localKey, RepositoryInterface $distant = null, $distantKey = null)
    {
        parent::__construct($attributeAim, $local, $distant);

        $this->localKey = $localKey;
        $this->distantKey = $distantKey;
    }

    //
    //----------- options
    //

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options)
    {
        parent::setOptions($options);

        if (!empty($options['saveStrategy'])) {
            $this->setSaveStrategy($options['saveStrategy']);
        }

        if (isset($options['discriminator'])) {
            $this->setDiscriminator($options['discriminator']);
        }

        if (isset($options['discriminatorValue'])) {
            $this->setDiscriminatorValue($options['discriminatorValue']);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        return parent::getOptions() + [
            'saveStrategy'          => $this->saveStrategy,
            'discriminator'         => $this->discriminator,
            'discriminatorValue'    => $this->discriminatorValue,
        ];
    }

    /**
     * Set the save strategy
     *
     * @param int $strategy
     *
     * @return $this
     */
    public function setSaveStrategy($strategy)
    {
        $this->saveStrategy = (int) $strategy;

        return $this;
    }

    /**
     * Get the save strategy
     *
     * @return int
     */
    public function getSaveStrategy()
    {
        return $this->saveStrategy;
    }

    //
    //------------ methods for loading relation
    //

    /**
     * {@inheritdoc}
     */
    #[ReadOperation]
    public function load(EntityIndexerInterface $collection, array $with = [], $constraints = [], array $without = [])
    {
        if ($collection->empty()) {
            return;
        }

        $indexed = $collection->by($this->localKey);

        $this->match($indexed, $this->relations(array_keys($indexed), $with, $constraints, $without));
    }

    /**
     * Get the entities
     *
     * @param array $keys
     * @param array $with
     * @param array $constraints
     * @param array $without
     *
     * @return array  Entities
     * @throws PrimeException
     */
    #[ReadOperation]
    abstract protected function relations($keys, $with, $constraints, $without);

    /**
     * Set the relation in a collection of entities
     *
     * @param array $collection
     * @param array $relations
     */
    abstract protected function match($collection, $relations);

    /**
     * Get defined relation
     *
     * Build object relation defined by user
     *
     * @param RepositoryInterface $repository
     * @param string              $relationName
     * @param array               $relationMeta
     *
     * @return RelationInterface
     *
     * @throws \RuntimeException If relation type does not exist
     */
    public static function make(RepositoryInterface $repository, $relationName, array $relationMeta)
    {
        switch ($relationMeta['type']) {
            case RelationInterface::BELONGS_TO:
                $relation = new BelongsTo(
                    $relationName,
                    $repository,
                    $relationMeta['localKey'],
                    $repository->repository($relationMeta['entity']),
                    $relationMeta['distantKey']
                );
                break;

            case RelationInterface::HAS_ONE:
                $relation = new HasOne(
                    $relationName,
                    $repository,
                    $relationMeta['localKey'],
                    $repository->repository($relationMeta['entity']),
                    $relationMeta['distantKey']
                );
                break;

            case RelationInterface::HAS_MANY:
                $relation = new HasMany(
                    $relationName,
                    $repository,
                    $relationMeta['localKey'],
                    $repository->repository($relationMeta['entity']),
                    $relationMeta['distantKey']
                );
                break;

            case RelationInterface::BELONGS_TO_MANY:
                $relation = new BelongsToMany(
                    $relationName,
                    $repository,
                    $relationMeta['localKey'],
                    $repository->repository($relationMeta['entity']),
                    $relationMeta['distantKey']
                );
                $relation->setThrough(
                    $repository->repository($relationMeta['through']),
                    $relationMeta['throughLocal'],
                    $relationMeta['throughDistant']
                );
                break;

            //---- polymorphism

            case RelationInterface::BY_INHERITANCE:
                $relation = new ByInheritance(
                    $relationName,
                    $repository,
                    $relationMeta['localKey']
                );
                break;

            case RelationInterface::MORPH_TO:
                $relation = new MorphTo(
                    $relationName,
                    $repository,
                    $relationMeta['localKey']
                );
                $relation->setMap($relationMeta['map']);
                break;

            case RelationInterface::CUSTOM:
                $relation = $relationMeta['relationClass']::make($repository, $relationName, $relationMeta);
                break;

            default:
                throw new \RuntimeException('Unknown type from relation "' . $relationName . '" in ' . $repository->entityName());
        }

        return $relation->setOptions($relationMeta);
    }

    /**
     * Create the array of relation
     *
     * [relation => constraints] becomes [relation => ['constraints' => constraints, 'relations' => subRelations]]
     *
     * ex
     * <code>
     *     print_r(Relation::sanitizeRelations([
     *         'customer.packs' => ['enabled' => true],
     *     ));
     *
     *     // echo an array like [
     *     //     'customer' => [
     *     //         'constraints' => [],
     *     //         'relations'   => ['packs' => ['enabled' => true]],
     *     //     ]
     *     // ]
     * </code>
     *
     * @param array $relations
     *
     * @return array
     *
     * @todo voir pour intégrer en meta le polymorphism
     */
    public static function sanitizeRelations(array $relations)
    {
        $sanitized = [];

        foreach ($relations as $name => $constraints) {
            if (is_int($name)) {
                $name = $constraints;
                $constraints = [];
            }

            // relation deja declaré: on ajoute ecrase les constraints
            // cas d'appel: ['foo.bar', 'foo' => constraints]
            if (isset($sanitized[$name])) {
                $sanitized[$name]['constraints'] = $constraints;
                continue;
            }

            $relations = [];

            list($name, $nested) = self::parseRelationName($name);

            // nested relation
            if ($nested) {
                // la relation existe deja, on ajoute la nouvelle relation
                // cas d'appel ['foo.bar1', 'foo.bar2' => constraints]
                if (isset($sanitized[$name])) {
                    $sanitized[$name]['relations'][$nested] = $constraints;
                    continue;
                }

                $relations[$nested] = $constraints;
                $constraints = [];
            }

            // declaration d'une relation à charger
            $sanitized[$name] = [
                'constraints' => $constraints,
                'relations'   => $relations,
            ];
        }

        return $sanitized;
    }

    /**
     * Create an array of relations and nested relations that must be discarded
     *
     * @param array $relations
     *
     * @return array
     */
    public static function sanitizeWithoutRelations(array $relations)
    {
        $sanitized = [];

        foreach ($relations as $name) {
            list($name, $nested) = self::parseRelationName($name);

            if (!isset($sanitized[$name])) {
                $sanitized[$name] = [];
            }

            if ($nested) {
                $sanitized[$name][] = $nested;
            }
        }

        return $sanitized;
    }

    /**
     * Parse the relation name to find whether a nested relation is defined or not
     *
     * @param string $name
     *
     * @return array
     */
    public static function parseRelationName($name)
    {
        if (strpos($name, '.') === false) {
            return [$name, null];
        }

        list($name, $relation) = explode('.', $name, 2);

        // gestion du polymorph de relation
        $part = explode('#', $name, 2);
        if (count($part) === 2) {
            $name = $part[0];
            $relation = $part[1].'#'.$relation;
        }

        return [$name, $relation];
    }

    /**
     * Get entity classname and property from a pattern
     *
     * @param string $pattern
     *
     * @return array
     */
    public static function parseEntity($pattern)
    {
        $parts = explode('::', $pattern, 2);

        return [
            $parts[0],
            isset($parts[1]) ? $parts[1] : 'id',
        ];
    }
}
