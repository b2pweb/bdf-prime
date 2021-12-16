<?php

namespace Bdf\Prime;

use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Mapper;

class TestEmbeddedEntity extends Model
{
    public $id;
    public $name;
    public $city;
    
    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param mixed $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }
}

class TestEmbeddedEntityMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'database' => 'test',
            'table' => 'foreign_',
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')
                ->sequence()->alias('pk_id')

            ->string('name', 90)
                ->alias('name_')
                
            ->string('city', 90)
                ->nillable()
        ;
    }
    
    /**
     * {@inheritdoc}
     */
    public function indexes(): array
    {
        return [
            ['name'],
            'id_name' => ['id', 'name'],
        ];
    }
}
