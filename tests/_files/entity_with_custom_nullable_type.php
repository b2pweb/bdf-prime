<?php

namespace Bdf\Prime;

use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Types\AbstractFacadeType;

class MyCustomNullableEntity extends Model
{
    public $id;
    public $foo;

    public function __construct(array $data = [])
    {
        $this->import($data);
    }
}

class MyCustomNullableEntityMapper extends Mapper
{
    /**
     * @inheritDoc
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table' => 'my_custom_nullable',
        ];
    }

    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->autoincrement()
            ->add('foo', Foo::class)
        ;
    }
}

class Foo
{
    public $v;

    public function __construct($v)
    {
        $this->v = $v;
    }
}

class FooType extends AbstractFacadeType
{
    public function __construct()
    {
        parent::__construct(Foo::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultType()
    {
        return self::STRING;
    }

    /**
     * {@inheritdoc}
     */
    public function fromDatabase($value, array $fieldOptions = [])
    {
        if ($value == '0') {
            return null;
        }

        return new Foo($value);
    }

    /**
     * {@inheritdoc}
     */
    public function toDatabase($value)
    {
        if ($value === null) {
            return '0';
        }

        return (string) $value->v;
    }

    /**
     * {@inheritdoc}
     */
    public function phpType(): string
    {
        return Foo::class;
    }
}
