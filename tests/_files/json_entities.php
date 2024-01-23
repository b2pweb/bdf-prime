<?php

namespace Bdf\Prime;

use Bdf\Prime\Entity\Model;
use Bdf\Prime\Mapper\Builder\FieldBuilder;
use Bdf\Prime\Mapper\Mapper;

class EntityWithJson extends Model
{
    public $id;
    public $data;
    public $object;

    public function __construct(array $data = [])
    {
        $this->import($data);
    }
}

class EntityWithJsonMapper extends Mapper
{
    public function schema(): array
    {
        return [
            'connection' => 'test',
            'table' => 'test_json',
        ];
    }

    public function buildFields(FieldBuilder $builder): void
    {
        $builder
            ->integer('id')->autoincrement()
            ->json('data')->useNativeJsonType()
            ->json('object')->useNativeJsonType(false)->jsonObjectAsArray(false)->nillable()
        ;
    }
}
