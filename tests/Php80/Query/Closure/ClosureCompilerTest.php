<?php

namespace Php80\Query\Closure;

use Bdf\Prime\TestEmbeddedEntity;
use Bdf\Prime\TestEntity;

class ClosureCompilerTest extends \Bdf\Prime\Query\Closure\ClosureCompilerTest
{
    public function test_allow_union_parameter_type()
    {
        $this->assertEquals("SELECT t0.* FROM test_ t0 WHERE t0.name LIKE '%foo%'", $this->query(fn (TestEntity|TestEmbeddedEntity $entity) => str_contains($entity->name, 'foo'), TestEntity::class)->toRawSql());
        $this->assertEquals("SELECT t0.* FROM foreign_ t0 WHERE t0.name_ LIKE '%foo%'", $this->query(fn (TestEntity|TestEmbeddedEntity $entity) => str_contains($entity->name, 'foo'), TestEmbeddedEntity::class)->toRawSql());
    }
}
