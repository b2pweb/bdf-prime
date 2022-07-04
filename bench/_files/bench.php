<?php

namespace Bdf\Prime\Bench;

use Bdf\Prime\Entity\Extensions\ArrayInjector;
use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Relations\Builder\RelationBuilder;

class UserRole
{
    const ADMINISTRATIVE_MANAGER = '1';
    const PROCUREMENT_MANAGER    = '2';
    const CHARTERER              = '3';
    const CARRIER                = '4';
    const MULTISITE_MANAGER      = '5';
}

class Customer extends Model
{
    use ArrayInjector;
    
    public $id;
    public $name;
    public $packs;
    
    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class Pack extends Model
{
    use ArrayInjector;
    
    public $id;
    public $label;
    
    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class CustomerPack extends Model
{
    use ArrayInjector;
    
    public $customerId;
    public $packId;
    
    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class User extends Model
{
    use ArrayInjector;
    
    public $id;
    public $name;
    public $customer;
    public $roles;
    
    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class UserMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'user',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->bigint('id')
                ->primary()
                
            ->string('name')
                
            ->searchableArray('roles')
                
            ->embedded('customer', 'Bdf\Prime\Bench\Customer', function($builder) {
                $builder->bigint('id')->alias('customer_id');
            })
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        $builder->on('customer')
            ->belongsTo('Bdf\Prime\Bench\Customer', 'customer.id');
    }
}

class CustomerMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'customer',
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->bigint('id')
                ->autoincrement()
                
            ->string('name')
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildRelations(RelationBuilder $builder): void
    {
        $builder->on('packs')
            ->belongsToMany('Bdf\Prime\Bench\Pack')
            ->through('Bdf\Prime\Bench\CustomerPack', 'customerId', 'packId');
    }
}

class PackMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'pack',
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')
                ->primary()
                
            ->string('label')
        ;
    }
}

class CustomerPackMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => 'customer_pack',
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->bigint('customerId')
                ->primary()->alias('customer_id')
                
            ->integer('packId')
                ->primary()->alias('pack_id')
        ;
    }
}

class EntityArrayOf extends Model
{
    use ArrayInjector;

    public $id;
    public $floats = [];
    public $booleans = [];
    public $dates = [];

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class EntityArrayOfMapper extends Mapper
{
    /**
     * @inheritDoc
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table' => 'array_of_entity'
        ];
    }

    /**
     * @inheritDoc
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->autoincrement()
            ->arrayOfDouble('floats')
            ->arrayOfDateTime('dates')
            ->arrayOf('booleans', 'boolean')
        ;
    }
}

class EntityNotTypedArray extends Model
{
    use ArrayInjector;

    public $id;
    public $floats = [];
    public $booleans = [];
    public $dates = [];

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class EntityNotTypedArrayMapper extends Mapper
{
    /**
     * @inheritDoc
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table' => 'array_not_typed_entity'
        ];
    }

    /**
     * @inheritDoc
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->autoincrement()
            ->simpleArray('floats')
            ->simpleArray('dates')
            ->simpleArray('booleans')
        ;
    }
}
