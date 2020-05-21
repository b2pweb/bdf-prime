<?php

namespace Bdf\Prime;

use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Mapper;

/**
 *
 */
class ArrayHydratorTestEntity2
{
    public $name;

    protected $phone;

    private $password;

    /**
     * @var EmbeddedEntity2
     */
    protected $ref;

    /**
     * @var EmbeddedEntity2
     */
    protected $ref2;

    /**
     * ArrayHydratorTestEntity2 constructor.
     */
    public function __construct()
    {
        $this->ref = new EmbeddedEntity2();
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param mixed $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return EmbeddedEntity2
     */
    public function getRef()
    {
        return $this->ref;
    }

    /**
     * @param EmbeddedEntity2 $ref
     */
    public function setRef($ref)
    {
        $this->ref = $ref;
    }

    /**
     * @return EmbeddedEntity2
     */
    public function getRef2()
    {
        return $this->ref2;
    }

    /**
     * @param EmbeddedEntity2 $ref2
     */
    public function setRef2($ref2)
    {
        $this->ref2 = $ref2;
    }
}

class ArrayHydratorTestEntity2Mapper extends Mapper
{
    /**
     * @inheritDoc
     */
    public function schema()
    {
        return [
            'connection' => 'test',
            'table' => 'test_array_hydrator2'
        ];
    }

    /**
     * @inheritDoc
     */
    public function buildFields($builder)
    {
        $builder
            ->string('name')->primary()
            ->string('phone')
            ->string('password')
            ->embedded('ref', EmbeddedEntity2::class, function (FieldBuilder $builder) {
                $builder->integer('id')->alias('ref_id');
            })
            ->embedded('ref2', EmbeddedEntity2::class, function (FieldBuilder $builder) {
                $builder->integer('id')->alias('ref2_id');
            });
    }
}


/**
 * Class EmbeddedEntity
 */
class EmbeddedEntity2
{
    private $id;

    /**
     * EmbeddedEntity constructor.
     * @param $id
     */
    public function __construct($id = null)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}

class EmbeddedEntity2Mapper extends Mapper
{
    /**
     * @inheritDoc
     */
    public function schema()
    {
        return [
            'connection' => 'test',
            'table' => 'test_array_hydrator_embbeded2'
        ];
    }

    /**
     * @inheritDoc
     */
    public function buildFields($builder)
    {
        $builder->add('id');
    }
}