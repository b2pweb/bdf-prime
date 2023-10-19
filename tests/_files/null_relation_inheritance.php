<?php

namespace Bdf\Prime;

use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Mapper\SingleTableInheritanceMapper;
use Bdf\Prime\Relations\Builder\RelationBuilder;

class BaseConfig extends Model
{
    public const TYPE_FOO = 'foo';
    public const TYPE_BAR = 'bar';

    public ?int $id = null;
    public ?string $type = null;
    public ?string $value;
    public ?object $extra = null;

    public function __construct(array $data = [])
    {
        $this->import($data);
    }
}

class BaseConfigMapper extends SingleTableInheritanceMapper
{
    protected $discriminatorColumn = 'type';
    protected $discriminatorMap = [
        BaseConfig::TYPE_FOO => FooConfigMapper::class,
        BaseConfig::TYPE_BAR => BarConfigMapper::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table' => 'config',
        ];
    }

    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->autoincrement()
            ->string('type')
            ->string('value')
        ;
    }

    public function buildRelations(RelationBuilder $builder): void
    {
        $builder->on('extra')->inherit('id')->eager();
    }
}

class FooConfig extends BaseConfig
{
    public ?string $type = self::TYPE_FOO;
}

class FooConfigMapper extends BaseConfigMapper
{
    public function buildRelations(RelationBuilder $builder): void
    {
        parent::buildRelations($builder);

        $builder->on('extra')->hasOne(FooExtraConfig::class);
    }
}

class FooExtraConfig extends Model
{
    public ?int $id = null;
    public ?string $foo = null;

    public function __construct(array $data = [])
    {
        $this->import($data);
    }
}

class FooExtraConfigMapper extends Mapper
{
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table' => 'config_foo_extra',
        ];
    }

    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->primary()
            ->string('foo')
        ;
    }
}

class BarConfig extends BaseConfig
{
    public ?string $type = self::TYPE_BAR;
}

class BarConfigMapper extends BaseConfigMapper
{
    public function buildRelations(RelationBuilder $builder): void
    {
        parent::buildRelations($builder);

        $builder->on('extra')->null();
    }
}
