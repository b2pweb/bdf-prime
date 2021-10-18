<?php

namespace Bdf\Prime\Bench;

use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Relations\Builder\RelationBuilder;
use Bdf\Prime\Entity\Extensions\ArrayInjector;
use Bdf\Prime\Entity\ImportableInterface;
use Bdf\Prime\Mapper\Mapper;

class UserRole
{
    const ADMINISTRATIVE_MANAGER = '1';
    const PROCUREMENT_MANAGER    = '2';
    const CHARTERER              = '3';
    const CARRIER                = '4';
    const MULTISITE_MANAGER      = '5';
}

class Customer implements ImportableInterface
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

class Pack implements ImportableInterface
{
    use ArrayInjector;
    
    public $id;
    public $label;
    
    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class CustomerPack implements ImportableInterface
{
    use ArrayInjector;
    
    public $customerId;
    public $packId;
    
    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

class User implements ImportableInterface
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