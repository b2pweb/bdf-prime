<?php

namespace Bdf\Prime\Entity;

use Bdf\Prime\Mapper\Mapper;

class Place extends Model
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var Bag
     */
    public $bag;
}

class PlaceMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema()
    {
        return [
            'connection' => 'test',
            'table' => 'place',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields($builder)
    {
        $builder->bigint('id')->primary();
        $builder->embedded('bag', Bag::class, function($builder) {
            $builder->embedded('foo', Foo::class, function($builder) {
                $builder->string('name', 45);
            });
            $builder->embedded('bar', Bar::class, function($builder) {
                $builder->string('name', 45);
            });
        });
    }
}

class Bag
{
    /**
     * @var Foo
     */
    public $foo;

    /**
     * @var Bar
     */
    public $bar;
}

class Foo
{
    /**
     * @var string
     */
    private $name;

    public function __construct($name = null)
    {
        $this->name = $name;
    }

    public function name()
    {
        return $this->name;
    }
}

class Bar
{
    /**
     * @var string
     */
    private $name;

    public function __construct($name = null)
    {
        $this->name = $name;
    }

    public function name()
    {
        return $this->name;
    }
}
