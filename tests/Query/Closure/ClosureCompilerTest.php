<?php

namespace Bdf\Prime\Query\Closure;

use Bdf\Prime\ArrayHydratorTestEntity;
use Bdf\Prime\Bench\UserRole;
use Bdf\Prime\EmbeddedEntity;
use Bdf\Prime\Entity\Model;
use Bdf\Prime\Faction;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Query;
use Bdf\Prime\TestEmbeddedEntity;
use Bdf\Prime\TestEntity;
use Bdf\Prime\User;
use Closure;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

class ClosureCompilerTest extends TestCase
{
    public const FILTER = 42;

    use PrimeTestCase;

    private ?CacheInterface $cache;

    protected function setUp(): void
    {
        $this->primeStart();
        $this->cache = null;
    }

    public function test_compile_simple()
    {
        $value = 5;
        $this->assertEquals(
            new NestedFilters([['id', '>', 5]]),
            $this->compiler(TestEntity::class)->compile(fn (TestEntity $entity) => $entity->id > $value)
        );
        $this->assertEquals(
            new NestedFilters([['id', '=', 42]]),
            $this->compiler(TestEntity::class)->compile(fn (TestEntity $entity) => $entity->id == 42)
        );
        $this->assertEquals(
            new NestedFilters([['dateInsert', '!=', null]]),
            $this->compiler(TestEntity::class)->compile(fn (TestEntity $entity) => $entity->dateInsert != null)
        );
        $this->assertEquals(
            new NestedFilters([['enabled', '=', true]]),
            $this->compiler(Faction::class)->compile(fn (Faction $entity) => $entity->enabled === true)
        );
        $this->assertEquals(
            new NestedFilters([['enabled', '=', true]]),
            $this->compiler(Faction::class)->compile(fn (Faction $entity) => $entity->enabled)
        );
        $this->assertEquals(
            new NestedFilters([['enabled', '=', false]]),
            $this->compiler(Faction::class)->compile(fn (Faction $entity) => !$entity->enabled)
        );
        $this->assertEquals(
            new NestedFilters([['id', '>=', 42], ['name', '=', 'foo']]),
            $this->compiler(TestEntity::class)->compile(fn (TestEntity $entity) => $entity->id >= 42 && $entity->name == 'foo')
        );
        $this->assertEquals(
            new NestedFilters([['name', ':like', '%foo%']]),
            $this->compiler(TestEntity::class)->compile(fn (TestEntity $entity) => str_contains($entity->name, 'foo'))
        );
        $this->assertEquals(
            new NestedFilters([['name', ':like', 'foo%']]),
            $this->compiler(TestEntity::class)->compile(fn (TestEntity $entity) => str_starts_with($entity->name, 'foo'))
        );
        $this->assertEquals(
            new NestedFilters([['name', ':like', '%foo']]),
            $this->compiler(TestEntity::class)->compile(fn (TestEntity $entity) => str_ends_with($entity->name, 'foo'))
        );
        $this->assertEquals(
            new NestedFilters([['id', ':in', [12, 23, 5]]]),
            $this->compiler(TestEntity::class)->compile(fn (TestEntity $entity) => in_array($entity->id, [12, 23, $value]))
        );
        $this->assertEquals(
            new NestedFilters([['customer.name', ':like', '%foo%']]),
            $this->compiler(User::class)->compile(fn (User $entity) => str_contains($entity->customer->name, 'foo'))
        );
        $this->assertEquals(
            new NestedFilters([['ref.id', '>', 5]]),
            $this->compiler(ArrayHydratorTestEntity::class)->compile(fn (ArrayHydratorTestEntity $entity) => $entity->getRef()->getId() > 5)
        );
        $this->assertEquals(
            new NestedFilters([[new NestedFilters([['id', '>', 42], ['id', '<', 7]], CompositeExpression::TYPE_OR), null, null]]),
            $this->compiler(TestEntity::class)->compile(fn (TestEntity $entity) => $entity->id > 42 || $entity->id < 7)
        );
        $this->assertEquals(
            new NestedFilters([[new NestedFilters([['id', '=', 12], ['id', '=', 42], ['id', '=', 7], ['id', '=', 5]], CompositeExpression::TYPE_OR), null, null]]),
            $this->compiler(TestEntity::class)->compile(fn (TestEntity $entity) => $entity->id == 12 || $entity->id == 42 || $entity->id == 7 || $entity->id == 5)
        );
    }

    public function test_compile_with_cache()
    {
        $this->cache = $cache = new class implements CacheInterface {
            public array $data = [];

            public function get($key, $default = null)
            {
                if (!array_key_exists($key, $this->data)) {
                    return $default;
                }

                return unserialize($this->data[$key]);
            }

            public function set($key, $value, $ttl = null)
            {
                $this->data[$key] = serialize($value);
            }

            public function delete($key) {}
            public function clear() {}
            public function getMultiple($keys, $default = null) {}
            public function setMultiple($values, $ttl = null) {}
            public function deleteMultiple($keys) {}
            public function has($key) {}
        };

        $this->test_compile_simple(); // For build cache
        $this->test_compile_simple(); // Test with cache

        $this->assertCount(15, $cache->data);
    }

    public function test_missing_parameter_type()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Closure parameter must declare the entity type');

        $this->compiler(TestEntity::class)->compile(fn ($entity) => $entity->id > 5);
    }

    public function test_invalid_entity_parameter()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expect parameter of type "Bdf\Prime\TestEntity" but get "Bdf\Prime\EmbeddedEntity"');

        $this->compiler(TestEntity::class)->compile(fn (EmbeddedEntity $entity) => $entity->getId() > 5);
    }

    public function test_invalid_parameter_count()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Closure must have only one parameter');

        $this->compiler(TestEntity::class)->compile(fn (EmbeddedEntity $entity, $foo) => $entity->getId() > 5);
    }

    public function test_property_not_found()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Property "notFound" is not mapped to database.');

        $this->compiler(TestEntity::class)->compile(fn (TestEntity $entity) => $entity->notFound > 5);
    }

    public function test_embedded_property_not_found()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Property "notFound.foo" is not mapped to database.');

        $this->compiler(TestEntity::class)->compile(fn (TestEntity $entity) => $entity->notFound->foo > 5);
    }

    public function test_closure_not_found()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No closure found');

        $this->compiler(TestEntity::class)->compile(Closure::fromCallable([$this, 'notClosure']));
    }

    public function test_invalid_operation()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unsupported expression Expr_PreInc in filters. Supported expressions are: binary operations, function calls getter for a boolean, and not expression.');

        $this->compiler(TestEntity::class)->compile(fn (TestEntity $entity) => ++$entity->id);
    }

    public function test_invalid_left_operand_expression()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid entity accessor Scalar_LNumber. Only properties and getters can be used in filters.');

        $this->compiler(TestEntity::class)->compile(fn (TestEntity $entity) => 5 == $entity->id);
    }

    public function test_left_operand_must_be_parameter_accessor()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The left operand of a comparison must be a property or a getter of the entity.');

        $foo = 5;
        $this->compiler(TestEntity::class)->compile(fn (TestEntity $entity) => $foo->bar == $entity->id);
    }

    public function test_dynamic_property_not_allowed()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Dynamic property access is not allowed in filters');

        $this->compiler(TestEntity::class)->compile(fn (TestEntity $entity) => $entity->{'id'} == 5);
    }

    public function test_entity_method_call_with_parameter_not_allowed()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Only getters can be used in filters');

        $this->compiler(TestEntity::class)->compile(fn (TestEntity $entity) => $entity->setId(5) == 5);
    }

    public function test_entity_dynamic_method_name_not_allowed()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Dynamic method name is not allowed in filters');

        $this->compiler(TestEntity::class)->compile(fn (TestEntity $entity) => $entity->{"id"}() == 5);
    }

    public function test_invalid_function_call()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unsupported function call foo in filters. Supported functions are: str_contains, str_starts_with, str_ends_with, in_array.');

        $this->compiler(TestEntity::class)->compile(fn (TestEntity $entity) => foo($entity->id));
    }

    public function test_unpack_argument_not_supported()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unsupported unpacking in function call in_array in filters.');

        $args = [[1, 2, 3]];
        $this->compiler(TestEntity::class)->compile(fn (TestEntity $entity) => in_array($entity->id, ...$args));
    }

    public function test_dynamic_class_constant_not_supported()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot resolve dynamic class constant. Use actual class name or store the constant into a variable.');

        $class = ClosureCompiler::class;
        $this->compiler(TestEntity::class)->compile(fn (TestEntity $entity) => $entity->name === $class::FILTER);
    }

    public function test_dynamic_property_fetch_not_supported()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot resolve dynamic property.');

        $obj = new \stdClass();
        $this->compiler(TestEntity::class)->compile(fn (TestEntity $entity) => $entity->name === $obj->{'foo'});
    }

    public function test_dynamic_method_name_not_supported()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot resolve dynamic method name.');

        $obj = new \stdClass();
        $this->compiler(TestEntity::class)->compile(fn (TestEntity $entity) => $entity->name === $obj->{'foo'}());
    }

    public function test_method_call_with_parameter_is_not_allowed()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot use method call with arguments in filters.');

        $obj = new \stdClass();
        $this->compiler(TestEntity::class)->compile(fn (TestEntity $entity) => $entity->name === $obj->foo(12));
    }

    public function test_compile_functional()
    {
        $this->assertEquals('SELECT t0.* FROM test_ t0 WHERE t0.id = 5', $this->query(fn (TestEntity $entity) => $entity->id == 5)->toRawSql());
        $this->assertEquals('SELECT t0.* FROM test_ t0 WHERE t0.id = 5 AND t0.name LIKE \'foo%\'', $this->query(fn (TestEntity $entity) => $entity->id == 5 && str_starts_with($entity->name, 'foo'))->toRawSql());
        $this->assertEquals('SELECT t0.* FROM test_ t0 WHERE t0.id = 5 OR t0.foreign_key = 5', $this->query(fn (TestEntity $entity) => $entity->id == 5 || $entity->foreign->id == 5)->toRawSql());
        $this->assertEquals(
            "SELECT t0.* FROM test_ t0 INNER JOIN foreign_ t1 ON t1.pk_id = t0.foreign_key WHERE (t0.id = 5 AND t0.name = 'John') OR (t0.foreign_key = 5 AND t1.name_ = 'John')",
            $this
                ->query(fn (TestEntity $entity) => ($entity->id == 5 && $entity->name == 'John') || ($entity->foreign->id == 5 && $entity->foreign->name === 'John'))
                ->toRawSql()
        );
        $this->assertEquals('SELECT t0.* FROM test_ t0 WHERE t0.id = 5', $this->query(function (TestEntity $entity) {
            return $entity->id == 5;
        })->toRawSql());
    }

    public function test_array_access_comparison_value()
    {
        $criteria = ['id' => 5, 'name' => 'John'];
        $this->assertEquals(
            "SELECT t0.* FROM test_ t0 WHERE t0.id = 5 AND t0.name LIKE 'John%'",
            $this
                ->query(fn (TestEntity $entity) => $entity->id == $criteria['id'] && str_starts_with($entity->name, $criteria['name']))
                ->toRawSql()
        );
    }

    public function test_class_constant_fetch_comparison_value()
    {
        $this->assertEquals(
            "SELECT t0.* FROM test_ t0 WHERE t0.id = 42",
            $this
                ->query(fn (TestEntity $entity) => $entity->id == ClosureCompilerTest::FILTER)
                ->toRawSql()
        );
        $this->assertEquals(
            "SELECT t0.* FROM user_ t0 WHERE t0.roles_ LIKE '%4%'",
            $this
                ->query(fn (User $entity) => str_contains($entity->roles, UserRole::CARRIER), User::class)
                ->toRawSql()
        );
    }

    public function test_property_access_comparison_value()
    {
        $criteria = (object) [
            'id' => 5,
            'name' => (object) [
                'first' => 'John',
                'last' => 'Doe',
            ],
        ];
        $this->assertEquals(
            "SELECT t0.* FROM test_ t0 WHERE t0.id = 5 AND t0.name LIKE 'John%' AND t0.name LIKE '%Doe'",
            $this
                ->query(fn (TestEntity $entity) =>
                    $entity->id == $criteria->id
                    && str_starts_with($entity->name, $criteria->name->first)
                    && str_ends_with($entity->name, $criteria->name->last)
                )
                ->toRawSql()
        );
    }

    public function test_getter_comparison_value()
    {
        $criteria = new class {
            public function id() { return 5; }
        };
        $this->assertEquals(
            "SELECT t0.* FROM test_ t0 WHERE t0.id = 5",
            $this
                ->query(fn (TestEntity $entity) => $entity->id == $criteria->id())
                ->toRawSql()
        );
    }

    public function test_this_access_comparison_value()
    {
        $this->assertEquals(
            "SELECT t0.* FROM test_ t0 WHERE t0.id = 42",
            $this
                ->query(fn (TestEntity $entity) => $entity->id == $this->getFilter())
                ->toRawSql()
        );
    }

    public function test_allow_subclass_typehint()
    {
        $this->assertEquals(
            "SELECT t0.* FROM test_ t0 WHERE t0.id = 5",
            $this
                ->query(fn (Model $entity) => $entity->id == 5)
                ->toRawSql()
        );
    }

    protected function compiler(string $entity): ClosureCompiler
    {
        return new ClosureCompiler($this->prime()->repository($entity), $this->cache);
    }

    protected function query(Closure $filter, string $entity = TestEntity::class): Query
    {
        return $this->prime()->repository($entity)->builder()->where($this->compiler($entity)->compile($filter));
    }

    public function notClosure(TestEntity $entity)
    {
        return true;
    }

    public function getFilter()
    {
        return 42;
    }
}
