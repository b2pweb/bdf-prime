<?php

namespace Bdf\Prime;

use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Mapper;

class CompositePkEntity extends Model
{
    protected $key1;
    protected $key2;
    protected $value;

    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }

    /**
     * @return mixed
     */
    public function key1()
    {
        return $this->key1;
    }

    /**
     * @param mixed $key1
     *
     * @return $this
     */
    public function setKey1($key1)
    {
        $this->key1 = $key1;

        return $this;
    }

    /**
     * @return mixed
     */
    public function key2()
    {
        return $this->key2;
    }

    /**
     * @param mixed $key2
     *
     * @return $this
     */
    public function setKey2($key2)
    {
        $this->key2 = $key2;

        return $this;
    }

    /**
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }
}

class CompositePkEntityMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table'      => '_composite_pk'
        ];
    }

    public function buildFields(FieldBuilder $builder): void
    {
        $builder->string('key1')->primary();
        $builder->string('key2')->primary();
        $builder->string('value')->nillable();
    }
}
