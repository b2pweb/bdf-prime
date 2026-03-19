<?php

namespace Bdf\Prime;

use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\Types\TypeInterface;

class EntityWithCustomStorageType extends Model
{
    public ?int $id;
    public ?string $name;
    public array $value = [];
    public bool $enabled = false;

    public function __construct(array $data = [])
    {
        $this->import($data);
    }
}

class EntityWithCustomStorageTypeMapper extends Mapper
{
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table' => 'entity_custom_storage_type',
        ];
    }

    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->autoincrement()->storedAs(TypeInterface::DOUBLE)
            ->string('name')->storedAs(TypeInterface::BLOB)
            ->simpleArray('value')->storedAs(TypeInterface::STRING)->length(32)
            ->boolean('enabled')->storedAs(TypeInterface::INTEGER)
        ;
    }
}
