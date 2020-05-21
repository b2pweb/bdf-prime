<?php

namespace Bdf\Prime;

use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Builder\PolymorphBuilder;
use Bdf\Prime\Mapper\Mapper;

class PolymorphContainer extends Model
{
    private $id;
    private $embedded;


    /**
     * PolymorphContainer constructor.
     *
     */
    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }

    /**
     * @return mixed
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function embedded()
    {
        return $this->embedded;
    }

    /**
     * @param mixed $embedded
     *
     * @return $this
     */
    public function setEmbedded($embedded)
    {
        $this->embedded = $embedded;

        return $this;
    }
}

class PolymorphSubA
{
    private $type = 'A';
    private $name;
    private $location;

    /**
     * PolymorphSubA constructor.
     *
     * @param $name
     */
    public function __construct($name = null)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function location()
    {
        return $this->location;
    }

    /**
     * @param mixed $location
     *
     * @return $this
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }
}

class PolymorphSubB
{
    private $type = 'B';
    private $name;
    private $location;

    /**
     * PolymorphSubB constructor.
     *
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function location()
    {
        return $this->location;
    }

    /**
     * @param mixed $location
     *
     * @return $this
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }
}

class PolymorphContainerMapper extends Mapper
{
    /**
     * @inheritDoc
     */
    public function schema()
    {
        return [
            'connection' => 'test',
            'table' => 'test_polymorh_embedded'
        ];
    }

    public function buildFields($builder)
    {
        $builder
            ->integer('id')->autoincrement()
            ->polymorph(
                'embedded',
                [
                    'A' => PolymorphSubA::class,
                    'B' => PolymorphSubB::class,
                ],
                function (PolymorphBuilder $builder) {
                    $builder
                        ->string('name')->alias('sub_name')
                        ->string('type')->alias('sub_type')->discriminator()
                        ->embedded('location', Location::class, function (FieldBuilder $builder) {
                            $builder
                                ->string('address')->alias('sub_address')
                                ->string('city')->alias('sub_city')
                            ;
                        })
                    ;
                }
            )
        ;
    }
}
