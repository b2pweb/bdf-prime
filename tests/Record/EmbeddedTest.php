<?php

namespace Bdf\Prime\Record;

use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\PrimeTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionParameter;

use function sprintf;

class EmbeddedTest extends TestCase
{
    use PrimeTestCase;

    protected function tearDown(): void
    {
        $this->unsetPrime();
    }

    public function test_without_attribute()
    {
        $this->assertNull(Embedded::fromReflectionParameter($this->parameter(EmbeddedHolder::class, 'id')));
        $this->assertNull(Embedded::fromReflectionParameter($this->parameter(EmbeddedHolder::class, 'id'), ['id' => ['field' => 'id_', 'type' => 'bigint']]));
    }

    public function test_dbal_default_prefix()
    {
        $embedded = Embedded::fromReflectionParameter($this->parameter(EmbeddedHolder::class, 'sub'));

        $this->assertSame('sub_', $embedded->prefix);
        $this->assertSame(EmbeddedSubRecord::class, $embedded->className);
        $this->assertSame(EmbeddedSubRecord::class, $embedded->instantiator->recordClass);
        $this->assertEquals([
            new Field('sub_name', castType: CastType::String, nullable: false),
            new Field('sub_value', castType: CastType::Integer, nullable: true),
        ], $embedded->instantiator->fields);

        $this->assertSame(['sub_name', 'sub_value'], $embedded->projection());
        $this->assertEquals(
            new EmbeddedSubRecord('foo', 42),
            $embedded->value($this->createMock(PlatformInterface::class), ['id' => 1, 'sub_name' => 'foo', 'sub_value' => '42'])
        );
    }

    public function test_orm_default_prefix()
    {
        $this->configurePrime();
        $platform = $this->prime()->connection('test')->platform();

        $embedded = Embedded::fromReflectionParameter($this->parameter(EmbeddedHolder::class, 'sub'), [
            'id' => ['field' => 'id_', 'type' => 'bigint'],
            'sub.name' => ['field' => 'sub_name_', 'type' => 'string'],
            'sub.value' => ['field' => 'sub_value_', 'type' => 'integer'],
        ]);

        $this->assertSame('sub.', $embedded->prefix);
        $this->assertSame(EmbeddedSubRecord::class, $embedded->className);
        $this->assertEquals([
            new Field('sub_name_', type: 'string', castType: CastType::String, nullable: false, projection: 'sub.name'),
            new Field('sub_value_', type: 'integer', castType: CastType::Integer, nullable: true, projection: 'sub.value'),
        ], $embedded->instantiator->fields);

        $this->assertSame(['sub.name', 'sub.value'], $embedded->projection());
        $this->assertEquals(
            new EmbeddedSubRecord('foo', 42),
            $embedded->value($platform, ['id_' => 1, 'sub_name_' => 'foo', 'sub_value_' => '42'])
        );
    }

    public function test_explicit_prefix()
    {
        $embedded = Embedded::fromReflectionParameter($this->parameter(EmbeddedHolder::class, 'withPrefix'));

        $this->assertSame('other_', $embedded->prefix);
        $this->assertSame(['other_name', 'other_value'], $embedded->projection());
        $this->assertEquals(
            new EmbeddedSubRecord('foo', 42),
            $embedded->value($this->createMock(PlatformInterface::class), ['other_name' => 'foo', 'other_value' => '42'])
        );
    }

    public function test_empty_prefix()
    {
        $embedded = Embedded::fromReflectionParameter($this->parameter(EmbeddedHolder::class, 'flat'));

        $this->assertSame('', $embedded->prefix);
        $this->assertSame(['name', 'value'], $embedded->projection());
        $this->assertEquals(
            new EmbeddedSubRecord('foo', 42),
            $embedded->value($this->createMock(PlatformInterface::class), ['name' => 'foo', 'value' => '42'])
        );
    }

    public function test_explicit_class_name()
    {
        $embedded = Embedded::fromReflectionParameter($this->parameter(EmbeddedHolder::class, 'untyped'));

        $this->assertSame('untyped_', $embedded->prefix);
        $this->assertSame(EmbeddedSubRecord::class, $embedded->className);
        $this->assertSame(['untyped_name', 'untyped_value'], $embedded->projection());
        $this->assertEquals(
            new EmbeddedSubRecord('foo', 42),
            $embedded->value($this->createMock(PlatformInterface::class), ['untyped_name' => 'foo', 'untyped_value' => '42'])
        );
    }

    public function test_class_name_should_take_precedence_over_the_parameter_type()
    {
        $embedded = Embedded::fromReflectionParameter($this->parameter(EmbeddedHolder::class, 'withClassName'));

        $this->assertSame(EmbeddedOtherSubRecord::class, $embedded->className);
        $this->assertSame(['withClassName_foo'], $embedded->projection());
        $this->assertEquals(
            new EmbeddedOtherSubRecord('bar'),
            $embedded->value($this->createMock(PlatformInterface::class), ['withClassName_foo' => 'bar'])
        );
    }

    public function test_field_prefix()
    {
        $embedded = Embedded::fromReflectionParameter($this->parameter(EmbeddedHolder::class, 'sub'), null, 'parent_');

        $this->assertSame('parent_sub_', $embedded->prefix);
        $this->assertSame(['parent_sub_name', 'parent_sub_value'], $embedded->projection());
        $this->assertEquals(
            new EmbeddedSubRecord('foo', 42),
            $embedded->value($this->createMock(PlatformInterface::class), ['parent_sub_name' => 'foo', 'parent_sub_value' => '42'])
        );
    }

    public function test_field_prefix_with_explicit_prefix()
    {
        $embedded = Embedded::fromReflectionParameter($this->parameter(EmbeddedHolder::class, 'withPrefix'), null, 'parent_');

        $this->assertSame('parent_other_', $embedded->prefix);
        $this->assertSame(['parent_other_name', 'parent_other_value'], $embedded->projection());
    }

    public function test_field_prefix_on_orm()
    {
        $this->configurePrime();
        $platform = $this->prime()->connection('test')->platform();

        $embedded = Embedded::fromReflectionParameter($this->parameter(EmbeddedHolder::class, 'sub'), [
            'parent.sub.name' => ['field' => 'sub_name_', 'type' => 'string'],
            'parent.sub.value' => ['field' => 'sub_value_', 'type' => 'integer'],
        ], 'parent.');

        $this->assertSame('parent.sub.', $embedded->prefix);
        $this->assertSame(['parent.sub.name', 'parent.sub.value'], $embedded->projection());
        $this->assertEquals(
            new EmbeddedSubRecord('foo', 42),
            $embedded->value($platform, ['sub_name_' => 'foo', 'sub_value_' => '42'])
        );
    }

    public function test_nested_embedded_on_dbal()
    {
        $embedded = Embedded::fromReflectionParameter($this->parameter(EmbeddedNestedHolder::class, 'nested'));

        $this->assertSame('nested_', $embedded->prefix);
        $this->assertSame(['nested_id', 'nested_sub_name', 'nested_sub_value'], $embedded->projection());
        $this->assertEquals(
            new EmbeddedNestedSubRecord(1, new EmbeddedSubRecord('foo', 42)),
            $embedded->value($this->createMock(PlatformInterface::class), [
                'nested_id' => '1',
                'nested_sub_name' => 'foo',
                'nested_sub_value' => '42',
            ])
        );
    }

    public function test_nested_embedded_on_orm()
    {
        $this->configurePrime();
        $platform = $this->prime()->connection('test')->platform();

        $embedded = Embedded::fromReflectionParameter($this->parameter(EmbeddedNestedHolder::class, 'nested'), [
            'nested.id' => ['field' => 'nested_id_', 'type' => 'bigint'],
            'nested.sub.name' => ['field' => 'sub_name_', 'type' => 'string'],
            'nested.sub.value' => ['field' => 'sub_value_', 'type' => 'integer'],
        ]);

        $this->assertSame('nested.', $embedded->prefix);
        $this->assertSame(['nested.id', 'nested.sub.name', 'nested.sub.value'], $embedded->projection());
        $this->assertEquals(
            new EmbeddedNestedSubRecord(1, new EmbeddedSubRecord('foo', 42)),
            $embedded->value($platform, [
                'nested_id_' => '1',
                'sub_name_' => 'foo',
                'sub_value_' => '42',
            ])
        );
    }

    public function test_custom_instantiator()
    {
        $embedded = Embedded::fromReflectionParameter($this->parameter(EmbeddedHolder::class, 'withInstantiator'));

        $this->assertSame('withInstantiator_', $embedded->prefix);
        $this->assertSame(EmbeddedOtherSubRecord::class, $embedded->instantiator->recordClass);
        $this->assertSame(['custom_foo'], $embedded->projection());
        $this->assertEquals(
            new EmbeddedOtherSubRecord('bar'),
            $embedded->value($this->createMock(PlatformInterface::class), ['custom_foo' => 'bar'])
        );
    }

    public function test_error_missing_type()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter missingType on Bdf\Prime\Record\EmbeddedInvalidHolder must have a type or the #[Embedded] attribute must define a className.');

        Embedded::fromReflectionParameter($this->parameter(EmbeddedInvalidHolder::class, 'missingType'));
    }

    public function test_error_builtin_type()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter builtinType on Bdf\Prime\Record\EmbeddedInvalidHolder must have a type or the #[Embedded] attribute must define a className.');

        Embedded::fromReflectionParameter($this->parameter(EmbeddedInvalidHolder::class, 'builtinType'));
    }

    public function test_error_union_type()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter unionType on Bdf\Prime\Record\EmbeddedInvalidHolder must have a type or the #[Embedded] attribute must define a className.');

        Embedded::fromReflectionParameter($this->parameter(EmbeddedInvalidHolder::class, 'unionType'));
    }

    private function parameter(string $class, string $name): ReflectionParameter
    {
        foreach (new ReflectionClass($class)->getConstructor()->getParameters() as $parameter) {
            if ($parameter->getName() === $name) {
                return $parameter;
            }
        }

        throw new InvalidArgumentException(sprintf('The parameter %s is not defined on %s', $name, $class));
    }
}

class EmbeddedSubRecord
{
    public function __construct(
        public readonly string $name,
        public readonly ?int $value,
    ) {}
}

class EmbeddedOtherSubRecord
{
    public function __construct(
        public readonly ?string $foo,
    ) {}
}

class EmbeddedNestedSubRecord
{
    public function __construct(
        public readonly int $id,
        #[Embedded]
        public readonly EmbeddedSubRecord $sub,
    ) {}
}

class EmbeddedHolder
{
    public function __construct(
        public readonly int $id,

        #[Embedded]
        public readonly EmbeddedSubRecord $sub,

        #[Embedded('other_')]
        public readonly EmbeddedSubRecord $withPrefix,

        #[Embedded('')]
        public readonly EmbeddedSubRecord $flat,

        #[Embedded(className: EmbeddedSubRecord::class)]
        public readonly mixed $untyped,

        #[Embedded(className: EmbeddedOtherSubRecord::class)]
        public readonly object $withClassName,

        #[Embedded(instantiator: new RecordInstantiator(EmbeddedOtherSubRecord::class, [new Field('custom_foo')]))]
        public readonly EmbeddedOtherSubRecord $withInstantiator,
    ) {}
}

class EmbeddedNestedHolder
{
    public function __construct(
        #[Embedded]
        public readonly EmbeddedNestedSubRecord $nested,
    ) {}
}

class EmbeddedInvalidHolder
{
    public function __construct(
        #[Embedded]
        public readonly mixed $missingType,

        #[Embedded]
        public readonly string $builtinType,

        #[Embedded]
        public readonly EmbeddedSubRecord|EmbeddedOtherSubRecord $unionType,
    ) {}
}
