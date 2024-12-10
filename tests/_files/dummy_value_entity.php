<?php


namespace Bdf\Prime;

use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Mapper;
use PhpParser\Node\Expr\AssignOp\Mod;

class EntityWithDummyValue extends Model
{
    public ?int $id = null;
    public ?string $name = null;
    public ?int $value = null;
    public ?bool $valid = null;

    public function __construct(array $data = [])
    {
        $this->import($data);
    }
}

class EntityWithDummyValueMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table' => 'dummy_value_entity',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->autoincrement()
            ->string('name')->dummy('?')
            ->integer('value')->dummy(-1)
            ->boolean('valid')->dummy(-1)
        ;
    }
}

class EmbeddedDummyValue extends Model
{
    public ?int $id = null;
    public ?EntityWithDummyValue $embedded = null;

    public function __construct(array $data = [])
    {
        $this->import($data);
    }
}

class EmbeddedDummyValueMapper extends Mapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table' => 'embedded_dummy_value',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->autoincrement()
            ->embedded('embedded', EntityWithDummyValue::class, function (FieldBuilder $builder) {
                $builder
                    ->integer('id')->alias('embedded_id')
                    ->string('name')->dummy('?')->alias('embedded_name')
                    ->integer('value')->dummy(-1)->alias('embedded_value')
                ;
            })
        ;
    }
}
