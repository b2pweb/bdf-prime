<?php


namespace Bdf\Prime;

use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Mapper;

class EntityWithVirtualProperty extends Model
{
    public ?int $id = null;
    public ?string $name = null;

    public function __construct(array $data = [])
    {
        $this->import($data);
    }
}

class EntityWithVirtualPropertyMapper extends Mapper
{
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table' => 'entity_with_virtual_property',
        ];
    }

    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->autoincrement()
            ->string('name', 50)->virtual()
        ;
    }
}
