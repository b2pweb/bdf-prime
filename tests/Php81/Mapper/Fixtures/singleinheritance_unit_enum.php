<?php

namespace Php81\Mapper\Fixtures;

use Bdf\Prime\Mapper\Attribute\DiscriminatorMap;
use Bdf\Prime\Mapper\Attribute\DiscriminatorMapEnumInterface;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Relations\Builder\RelationBuilder;
use Bdf\Prime\Entity\Extensions\ArrayInjector;
use Bdf\Prime\Mapper\SingleTableInheritanceMapper;
use Bdf\Prime\Mapper\Mapper;

class UnitParentEntity
{
    use ArrayInjector;

    public $id;
    public $name;
    public $targetId;
    public $target;
    public $typeId;
    public $dateInsert;


    public function __construct(array $attributes = [])
    {
        $this->import($attributes);
    }
}

enum TypeDiscriminatorUnitEnum implements DiscriminatorMapEnumInterface
{
    case Child1;
    case Child2;

    /**
     * @inheritDoc
     */
    public function mapperClass(): string
    {
        return match ($this) {
            self::Child1 => UnitChildEntity1Mapper::class,
            self::Child2 => UnitChildEntity2Mapper::class,
        };
    }
}

#[DiscriminatorMap('typeId', TypeDiscriminatorUnitEnum::class)]
class UnitParentEntityMapper extends SingleTableInheritanceMapper
{
    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'database'   => 'test',
            'table'      => 'parent_'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')
                ->autoincrement()
                
            ->string('name')
                
            ->unitEnum('typeId', TypeDiscriminatorUnitEnum::class)
                ->alias('type_id')
                
            ->bigint('targetId', 0)
                ->alias('target_id')
                
            ->datetime('dateInsert')->alias('date_insert')->nillable()
        ;
    }
}
class UnitChildEntity1 extends UnitParentEntity
{
    public $typeId = 'child1';
}
class UnitChildEntity1Mapper extends UnitParentEntityMapper
{
}
class UnitChildEntity2 extends UnitParentEntity
{
    public $typeId = 'child2';
}
class UnitChildEntity2Mapper extends UnitParentEntityMapper
{
}
