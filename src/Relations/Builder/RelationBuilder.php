<?php

namespace Bdf\Prime\Relations\Builder;

use Bdf\Prime\Relations\Relation;
use Bdf\Prime\Relations\RelationInterface;

/**
 * RelationBuilder
 *
 * @package Bdf\Prime\Relations\Builder
 */
class RelationBuilder implements \ArrayAccess, \IteratorAggregate
{
    const MODE_EAGER = "EAGER";

    /**
     * Array of relations definition
     *
     * @var array
     */
    protected $relations = [];

    /**
     * The name of the current relation
     *
     * @var string
     */
    private $current;


    /**
     * Get all defined relations
     *
     * @return array
     */
    public function relations()
    {
        return $this->relations;
    }

    /**
     * Specify the property to mark relation
     *
     * @param string $property      The property owner of the relation
     * 
     * @return $this
     */
    public function on($property)
    {
        $this->current = $property;
        
        return $this;
    }
    
    /**
     * Add a "belongs to" relation
     *
     * <code>
     * $builder->on('customer')->belongsTo('Customer', 'customer.id');
     * // or
     * $builder->on('customer')->belongsTo('Customer::id', 'customer.id');
     * </code>
     * 
     * @param string $entity        Class of the foreign entity
     * @param string $key           Owner's property name for the foreign key
     *
     * @return $this
     */
    public function belongsTo($entity, $key)
    {
        list($entity, $primaryKey) = Relation::parseEntity($entity);

        return $this->add(RelationInterface::BELONGS_TO, $key, [
            'entity'     => $entity,
            'distantKey' => $primaryKey,
        ]);
    }
    
    /**
     * Add a "has one" relation
     *
     * <code>
     * $builder->on('contact')->hasOne('Contact::distantId');
     * // or
     * $builder->on('contact')->hasOne('Contact::distantId', 'localId');
     * </code>
     * 
     * @param string $entity        Class of the foreign entity
     * @param string $foreignKey    Foreign entity's property name
     * @param string $key           Owner's property name for the foreign key
     *
     * @return $this
     */
    public function hasOne($entity, $key = 'id')
    {
        list($entity, $foreignKey) = Relation::parseEntity($entity);

        return $this->add(RelationInterface::HAS_ONE, $key, [
            'entity'     => $entity,
            'distantKey' => $foreignKey,
        ]);
    }
    
    /**
     * Add a "has many" relation
     *
     * <code>
     * $builder->on('documents')->hasMany('Document::distantId');
     * // or
     * $builder->on('documents')->hasMany('Document::distantId', 'localId');
     * </code>
     * 
     * @param string $entity        Class of the foreign entity
     * @param string $foreignKey    Foreign entity's property name
     * @param string $key           Owner's property name for the foreign key
     *
     * @return $this
     */
    public function hasMany($entity, $key = 'id')
    {
        list($entity, $foreignKey) = Relation::parseEntity($entity);

        return $this->add(RelationInterface::HAS_MANY, $key, [
            'entity'      => $entity,
            'distantKey'  => $foreignKey,
        ]);
    }
    
    /**
     * Add a "belongs to many" relation
     *
     * <code>
     * $builder->on('packs')->belongsToMany('Pack');
     * // or
     * $builder->on('packs')->belongsToMany('Pack::id');
     * // or
     * $builder->on('packs')->belongsToMany('Pack::id', 'localId');
     * </code>
     * 
     * @param string $entity                Class of the foreign entity
     * @param string $foreignKey            Foreign entity's property name
     * @param string $key                   Owner's property name for the foreign key
     *
     * @return $this
     */
    public function belongsToMany($entity, $key = 'id')
    {
        list($entity, $primaryKey) = Relation::parseEntity($entity);

        return $this->add(RelationInterface::BELONGS_TO_MANY, $key, [
            'entity'            => $entity,
            'distantKey'        => $primaryKey,
        ]);
    }

    /**
     * Add "through" infos
     *
     * <code>
     * $builder->on('customer')->belongsToMany('Pack')->through('CustomerPack', 'customerId', 'packId');
     * </code>
     *
     * @param string $through             Class of the through entity
     * @param string $throughLocal        The through property matches with "key"
     * @param string $throughDistant      The through property matches with "foreignKey"
     *
     * @return $this
     */
    public function through($through, $throughLocal, $throughDistant)
    {
        $this->relations[$this->current]['through'] = $through;
        $this->relations[$this->current]['throughLocal'] = $throughLocal;
        $this->relations[$this->current]['throughDistant'] = $throughDistant;

        return $this;
    }

    /**
     * Add a "morph to" relation
     *
     * <code>
     * $builder->on('uploader')->morphTo('uploaderId', 'uploaderType', [
     *     'admin' => 'Admin::id',
     *     'user'  => 'User::id',
     * ]);
     * </code>
     *
     * @param string $key               Owner's property name for the foreign key
     * @param string $discriminator     Polymorphism discriminator
     * @param array  $map               The polymorphism map. Contains entity and foreignKey
     *
     * @return $this
     */
    public function morphTo($key, $discriminator, array $map)
    {
        $this->add(RelationInterface::MORPH_TO, $key);

        return $this->map($discriminator, $map);
    }
    
    /**
     * Add a "morph one" relation
     *
     * <code>
     * $builder->on('uploader')->morphOne('Document::uploaderId', 'uploaderType=user');
     * //or
     * $builder->on('uploader')->morphOne('Document::uploaderId', 'uploaderType=user', 'id');
     * </code>
     *
     * @param string $entity             Class of the foreign entity
     * @param string $discriminator      Polymorphism discriminator
     * @param string $key                Owner's property name for the foreign key
     *
     * @return $this
     */
    public function morphOne($entity, $discriminator, $key = 'id')
    {
        list($discriminator, $discriminatorValue) = explode('=', $discriminator);

        return $this->hasOne($entity, $key)->morph($discriminator, $discriminatorValue);
    }

    /**
     * Add a "morph many" relation
     *
     * <code>
     * $builder->on('documents')->morphMany('Document::uploaderId', 'uploaderType=user');
     * //or
     * $builder->on('documents')->morphMany('Document::uploaderId', 'uploaderType=user', 'id');
     * </code>
     *
     * @param string $entity             Class of the foreign entity
     * @param string $discriminator      Polymorphism discriminator
     * @param string $key                Owner's property name for the foreign key
     *
     * @return $this
     */
    public function morphMany($entity, $discriminator, $key = 'id')
    {
        list($discriminator, $discriminatorValue) = explode('=', $discriminator);

        return $this->hasMany($entity, $key)->morph($discriminator, $discriminatorValue);
    }

    /**
     * Add a "by inheritance" relation
     *
     * @param string $key  Owner's property name for the foreign key
     * 
     * @return $this
     */
    public function inherit($key)
    {
        return $this->add(RelationInterface::BY_INHERITANCE, $key);
    }

    /**
     * Add a "custom" relation
     *
     * /!\ Due to its dynamic aspect, it's advisable to detach the relation (Hydrators may not handle the relation)
     *
     * <code>
     * $builder->on('my_relation')
     *     ->custom(MyRelationType::class, ['keys' => [...]])
     *     ->detached()
     * ;
     * </code>
     *
     * @param string $relationClass The relation class name
     * @param array $options The relation options
     *
     * @return $this
     */
    public function custom($relationClass, array $options = [])
    {
        $this->relations[$this->current] = ['type' => RelationInterface::CUSTOM, 'relationClass' => $relationClass] + $options;

        return $this;
    }

    /**
     * Set the relation entity
     * This method may be used with custom relation to set the distant entity
     *
     * The method accept formats :
     * - EntityClass::distantKey : entity is "EntityClass" and distant key is "distantKey"
     * - EntityClass             : entity is "EntityClass" and distant key is "id"
     *
     * <code>
     * $builder->on('my_relation')
     *     ->custom(MyRelationType::class)
     *     ->entity(MyDistantEntity::class)
     * ;
     * </code>
     *
     * @param string $entity The entity class name with the distant key
     *
     * @return $this
     *
     * @see RelationBuilder::custom()
     */
    public function entity($entity)
    {
        list($entity, $foreignKey) = Relation::parseEntity($entity);

        $this->relations[$this->current]['entity'] = $entity;
        $this->relations[$this->current]['distantKey'] = $foreignKey;

        return $this;
    }

    /**
     * Set an option to the relation
     *
     * <code>
     * $builder->on('my_relation')
     *     ->custom(MyRelationType::class)
     *     ->option('keys', ['key' => 'value'])
     * ;
     * </code>
     *
     * @param string $name The option name
     * @param mixed $value The option value
     *
     * @return $this
     */
    public function option($name, $value)
    {
        $this->relations[$this->current][$name] = $value;

        return $this;
    }
    
    /**
     * Add a relation
     *
     * @param string $type      Type of relation
     * @param string $key
     * @param array  $options
     *
     * @return $this
     */
    protected function add($type, $key, array $options = [])
    {
        $this->relations[$this->current] = ['type' => $type, 'localKey' => $key] + $options;
        
        return $this;
    }

    /**
     * Add a polymorph map.
     * 
     * Only for belongs to relation
     *
     * @param string $discriminator     Polymorphism discriminator
     * @param array  $map               The polymorphism map. COntains entity and foreignKey
     *
     * @return $this
     */
    protected function map($discriminator, array $map)
    {
        $this->relations[$this->current]['discriminator'] = $discriminator;
        $this->relations[$this->current]['map'] = $map;
        
        return $this;
    }
    
    /**
     * Add a morph value.
     * 
     * Only for Has* relation
     *
     * <code>
     * $builder->on('documents')->hasOne('Document::uploaderId')->morph('uploaderType', 'user');
     * </code>
     *
     * @param string $discriminator      Polymorphism discriminator
     * @param string $value              The value of the discriminator
     *
     * @return $this
     */
    public function morph($discriminator, $value)
    {
        $this->relations[$this->current]['discriminator'] = $discriminator;
        $this->relations[$this->current]['discriminatorValue'] = $value;

        return $this;
    }
    
    /**
     * Add constraints on relation
     *
     * <code>
     * // only where customer.enabled = true
     * $builder->on('customer')->constraints(['enabled' => true]);
     * </code>
     * 
     * @param array|\Closure $constraints   The globale constraints for this relation
     *
     * @return $this
     */
    public function constraints($constraints)
    {
        $this->relations[$this->current]['constraints'] = $constraints;
        
        return $this;
    }
    
    /**
     * Tag the relation as detached
     * 
     * Is the property embedded in the owner class
     * 
     * <code>
     * $builder->on('customer')->detached();
     * </code>
     * 
     * @param bool $flag
     *
     * @return $this
     */
    public function detached($flag = true)
    {
        $this->relations[$this->current]['detached'] = $flag;
        
        return $this;
    }

    /**
     * Set the save cascade strategy
     *
     * see relation constantes 'Relation::SAVE_STRATEGY_*'
     *
     * <code>
     * $builder->on('customer')->saveStrategy(Relation::SAVE_STRATEGY_REPLACE);
     * </code>
     *
     * @param int $strategy
     *
     * @return $this
     */
    public function saveStrategy($strategy)
    {
        $this->relations[$this->current]['saveStrategy'] = $strategy;

        return $this;
    }

    /**
     * Set the query's result wrapper.
     * works like Query::wrapAs()
     *
     * /!\ This method has no effects on detached relation
     *
     * <code>
     * $builder->on('packs')->wrapAs('collection');
     * </code>
     *
     * @param string|callable $wrapper The wrapper name. Can be 'collection' for use EntityCollection
     *
     * @return $this
     *
     * @see \Bdf\Prime\Collection\CollectionInterface
     * @see \Bdf\Prime\Query\Query::wrapAs()
     */
    public function wrapAs($wrapper)
    {
        $this->relations[$this->current]['wrapper'] = $wrapper;

        return $this;
    }

    //---- iterator interface
    
    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->relations);
    }
    
    //---- array access interface
    
    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->relations[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($key)
    {
        return $this->relations[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($key, $value)
    {
        // not allowed
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        // not allowed
    }

    /**
     * Set the relation fetch mode
     *
     * @param string $mode
     * @return $this
     */
    public function mode($mode)
    {
        $this->relations[$this->current]['mode'] = $mode;

        return $this;
    }
}