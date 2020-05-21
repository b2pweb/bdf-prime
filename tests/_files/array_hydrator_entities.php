<?php

namespace Bdf\Prime;

use Bdf\Prime\Entity\Extensions\ArrayInjector;
use Bdf\Prime\Entity\ImportableInterface;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Mapper;

/**
 * Class ArrayHydratorTestEntity
 */
class ArrayHydratorTestEntity implements ImportableInterface
{
    use ArrayInjector;

    public $name;

    protected $phone;

    private $password;

    /**
     * @var EmbeddedEntity
     */
    protected $ref;

    /**
     * @var EmbeddedEntity
     */
    protected $ref2;

    /**
     * ArrayHydratorTestEntity constructor.
     */
    public function __construct()
    {
        $this->ref = new EmbeddedEntity();
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
     * @return EmbeddedEntity
     */
    public function getRef()
    {
        return $this->ref;
    }

    /**
     * @param EmbeddedEntity $ref
     */
    public function setRef($ref)
    {
        $this->ref = $ref;
    }

    /**
     * @return EmbeddedEntity
     */
    public function getRef2()
    {
        return $this->ref2;
    }

    /**
     * @param EmbeddedEntity $ref2
     */
    public function setRef2($ref2)
    {
        $this->ref2 = $ref2;
    }
}

class ArrayHydratorTestEntityMapper extends Mapper
{
    /**
     * @inheritDoc
     */
    public function schema()
    {
        return [
            'connection' => 'test',
            'table' => 'test_array_hydrator'
        ];
    }

    /**
     * @inheritDoc
     */
    public function buildFields($builder)
    {
        $builder
            ->string('name')
            ->string('phone')
            ->string('password')
            ->embedded('ref', EmbeddedEntity::class, function (FieldBuilder $builder) {
                $builder->integer('id');
            })
            ->embedded('ref2', EmbeddedEntity::class, function (FieldBuilder $builder) {
                $builder->integer('id');
            });
    }
}


/**
 * Class EmbeddedEntity
 */
class EmbeddedEntity implements ImportableInterface
{
    use ArrayInjector;

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

class EmbeddedEntityMapper extends Mapper
{
    /**
     * @inheritDoc
     */
    public function schema()
    {
        return [
            'connection' => 'test',
            'table' => 'test_array_hydrator_embbeded'
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