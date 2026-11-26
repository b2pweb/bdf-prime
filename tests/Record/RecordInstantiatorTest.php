<?php

namespace Bdf\Prime\Record;

use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Raw;
use Bdf\Prime\Types\TypeInterface;
use PHPUnit\Framework\TestCase;

class RecordInstantiatorTest extends TestCase
{
    use PrimeTestCase;

    protected function tearDown(): void
    {
        $this->unsetPrime();
    }

    public function test_simple()
    {
        $instantiator = RecordInstantiator::fromRecordClass(SimpleRecord::class);

        $this->assertSame(SimpleRecord::class, $instantiator->recordClass);
        $this->assertEquals([
            new Field('name', castType: CastType::String, nullable: false),
            new Field('value', castType: CastType::Integer, nullable: false),
        ], $instantiator->fields);

        $this->assertSame(['name', 'value'], $instantiator->projection());
        $this->assertEquals(new SimpleRecord('foo', 123), $instantiator->instantiate(['name' => 'foo', 'value' => 123], $this->createMock(PlatformInterface::class)));
    }

    public function test_name_mapping()
    {
        $instantiator = RecordInstantiator::fromRecordClass(RecordWithNameMapping::class);

        $this->assertSame(RecordWithNameMapping::class, $instantiator->recordClass);
        $this->assertEquals([
            new Field('_name', castType: CastType::String, nullable: false),
            new Field('_value', castType: CastType::Integer, nullable: false),
        ], $instantiator->fields);

        $this->assertSame(['_name', '_value'], $instantiator->projection());
        $this->assertEquals(new RecordWithNameMapping('foo', 123), $instantiator->instantiate(['_name' => 'foo', '_value' => 123], $this->createMock(PlatformInterface::class)));
    }

    public function test_dbal_type()
    {
        $this->configurePrime();
        $platform = $this->prime()->connection('test')->platform();

        $instantiator = RecordInstantiator::fromRecordClass(RecordWithDbalType::class);

        $this->assertSame(RecordWithDbalType::class, $instantiator->recordClass);
        $this->assertEquals([
            new Field('name', castType: CastType::String, nullable: false),
            new Field('value', type: 'datetime', castType: CastType::Mixed, nullable: false),
        ], $instantiator->fields);

        $this->assertSame(['name', 'value'], $instantiator->projection());
        $this->assertEquals(new RecordWithDbalType('foo', new \DateTime('2025-02-01T15:25:03')), $instantiator->instantiate(['name' => 'foo', 'value' => '2025-02-01 15:25:03'], $platform));
    }

    public function test_with_expression()
    {
        $instantiator = RecordInstantiator::fromRecordClass(RecordWithExpression::class);

        $this->assertSame(RecordWithExpression::class, $instantiator->recordClass);
        $this->assertEquals([
            new Field('name', castType: CastType::String, nullable: false),
            new Field('value', expression: new Raw('foo'), castType: CastType::Integer, nullable: false),
        ], $instantiator->fields);

        $this->assertEquals(['name', 'value' => new Raw('foo')], $instantiator->projection());
        $this->assertEquals(new RecordWithExpression('foo', 123), $instantiator->instantiate(['name' => 'foo', 'value' => '123'], $this->createMock(PlatformInterface::class)));
    }

    public function test_with_projection()
    {
        $instantiator = RecordInstantiator::fromRecordClass(RecordWithCustomProjection::class);

        $this->assertSame(RecordWithCustomProjection::class, $instantiator->recordClass);
        $this->assertEquals([
            new Field('name', castType: CastType::String, nullable: false, projection: 't2.jajajaja'),
            new Field('value', castType: CastType::Integer, nullable: false, projection: false),
        ], $instantiator->fields);

        $this->assertEquals(['t2.jajajaja'], $instantiator->projection());
        $this->assertEquals(new RecordWithCustomProjection('foo', 123), $instantiator->instantiate(['name' => 'foo', 'value' => '123'], $this->createMock(PlatformInterface::class)));
    }

    public function test_error_without_constructor()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The record class Bdf\Prime\Record\RecordWithoutConstructor must have a constructor');

        RecordInstantiator::fromRecordClass(RecordWithoutConstructor::class);
    }

    public function test_with_field_prefix()
    {
        $instantiator = RecordInstantiator::fromRecordClass(SimpleRecord::class, fieldPrefix: 'foo_');

        $this->assertEquals([
            new Field('foo_name', castType: CastType::String, nullable: false),
            new Field('foo_value', castType: CastType::Integer, nullable: false),
        ], $instantiator->fields);

        $this->assertSame(['foo_name', 'foo_value'], $instantiator->projection());
        $this->assertEquals(
            new SimpleRecord('foo', 123),
            $instantiator->instantiate(['foo_name' => 'foo', 'foo_value' => 123, 'name' => 'ignored'], $this->createMock(PlatformInterface::class))
        );
    }

    public function test_with_empty_field_prefix()
    {
        $instantiator = RecordInstantiator::fromRecordClass(SimpleRecord::class, fieldPrefix: '');

        $this->assertEquals([
            new Field('name', castType: CastType::String, nullable: false),
            new Field('value', castType: CastType::Integer, nullable: false),
        ], $instantiator->fields);

        $this->assertSame(['name', 'value'], $instantiator->projection());
    }

    public function test_with_field_prefix_should_be_applied_on_mapped_name()
    {
        $instantiator = RecordInstantiator::fromRecordClass(RecordWithNameMapping::class, fieldPrefix: 'foo_');

        $this->assertEquals([
            new Field('foo__name', castType: CastType::String, nullable: false),
            new Field('foo__value', castType: CastType::Integer, nullable: false),
        ], $instantiator->fields);

        $this->assertSame(['foo__name', 'foo__value'], $instantiator->projection());
    }

    public function test_with_attributes_metadata()
    {
        $this->configurePrime();
        $platform = $this->prime()->connection('test')->platform();

        $instantiator = RecordInstantiator::fromRecordClass(SimpleRecord::class, [
            'name' => ['field' => 'name_', 'type' => 'string'],
            'value' => ['field' => 'value_', 'type' => 'integer'],
        ]);

        $this->assertEquals([
            new Field('name_', type: 'string', castType: CastType::String, nullable: false, projection: 'name'),
            new Field('value_', type: 'integer', castType: CastType::Integer, nullable: false, projection: 'value'),
        ], $instantiator->fields);

        $this->assertSame(['name', 'value'], $instantiator->projection());
        $this->assertEquals(new SimpleRecord('foo', 123), $instantiator->instantiate(['name_' => 'foo', 'value_' => '123'], $platform));
    }

    public function test_with_attributes_metadata_and_field_prefix()
    {
        $this->configurePrime();
        $platform = $this->prime()->connection('test')->platform();

        $instantiator = RecordInstantiator::fromRecordClass(SimpleRecord::class, [
            'sub.name' => ['field' => 'name_', 'type' => 'string'],
            'sub.value' => ['field' => 'value_', 'type' => 'integer'],
        ], 'sub.');

        $this->assertEquals([
            new Field('name_', type: 'string', castType: CastType::String, nullable: false, projection: 'sub.name'),
            new Field('value_', type: 'integer', castType: CastType::Integer, nullable: false, projection: 'sub.value'),
        ], $instantiator->fields);

        $this->assertSame(['sub.name', 'sub.value'], $instantiator->projection());
        $this->assertEquals(new SimpleRecord('foo', 123), $instantiator->instantiate(['name_' => 'foo', 'value_' => '123'], $platform));
    }

    public function test_with_attributes_metadata_and_unknown_field()
    {
        $instantiator = RecordInstantiator::fromRecordClass(SimpleRecord::class, [
            'name' => ['field' => 'name_', 'type' => 'string'],
        ]);

        $this->assertEquals([
            new Field('name_', type: 'string', castType: CastType::String, nullable: false, projection: 'name'),
            new Field('value', castType: CastType::Integer, nullable: false, projection: 'value'),
        ], $instantiator->fields);

        $this->assertSame(['name', 'value'], $instantiator->projection());
    }

    /**
     * The projection explicitly defined on the Field attribute (custom alias, or false to disable it)
     * must be kept when the attributes metadata are resolved.
     */
    public function test_with_attributes_metadata_should_keep_custom_projection()
    {
        $instantiator = RecordInstantiator::fromRecordClass(RecordWithCustomProjection::class, [
            'name' => ['field' => 'name_', 'type' => 'string'],
            'value' => ['field' => 'value_', 'type' => 'integer'],
        ]);

        $this->assertEquals(['t2.jajajaja'], $instantiator->projection());
    }

    public function test_with_embedded()
    {
        $instantiator = RecordInstantiator::fromRecordClass(RecordWithEmbedded::class);

        $this->assertSame(RecordWithEmbedded::class, $instantiator->recordClass);
        $this->assertEquals([
            new Field('id', castType: CastType::Integer, nullable: false),
            Embedded::fromReflectionParameter(new \ReflectionClass(RecordWithEmbedded::class)->getConstructor()->getParameters()[1]),
        ], $instantiator->fields);

        $this->assertSame(['id', 'sub_name', 'sub_value'], $instantiator->projection());
        $this->assertEquals(
            new RecordWithEmbedded(1, new SimpleRecord('foo', 123)),
            $instantiator->instantiate(['id' => '1', 'sub_name' => 'foo', 'sub_value' => '123'], $this->createMock(PlatformInterface::class))
        );
    }

    public function test_with_embedded_and_field_prefix()
    {
        $instantiator = RecordInstantiator::fromRecordClass(RecordWithEmbedded::class, fieldPrefix: 'root_');

        $this->assertSame(['root_id', 'root_sub_name', 'root_sub_value'], $instantiator->projection());
        $this->assertEquals(
            new RecordWithEmbedded(1, new SimpleRecord('foo', 123)),
            $instantiator->instantiate(['root_id' => '1', 'root_sub_name' => 'foo', 'root_sub_value' => '123'], $this->createMock(PlatformInterface::class))
        );
    }

    public function test_with_embedded_and_attributes_metadata()
    {
        $this->configurePrime();
        $platform = $this->prime()->connection('test')->platform();

        $instantiator = RecordInstantiator::fromRecordClass(RecordWithEmbedded::class, [
            'id' => ['field' => 'id_', 'type' => 'bigint'],
            'sub.name' => ['field' => 'sub_name_', 'type' => 'string'],
            'sub.value' => ['field' => 'sub_value_', 'type' => 'integer'],
        ]);

        $this->assertSame(['id', 'sub.name', 'sub.value'], $instantiator->projection());
        $this->assertEquals(
            new RecordWithEmbedded(1, new SimpleRecord('foo', 123)),
            $instantiator->instantiate(['id_' => '1', 'sub_name_' => 'foo', 'sub_value_' => '123'], $platform)
        );
    }

    public function test_with_flat_embedded()
    {
        $instantiator = RecordInstantiator::fromRecordClass(RecordWithFlatEmbedded::class);

        $this->assertSame(['id', 'name', 'value'], $instantiator->projection());
        $this->assertEquals(
            new RecordWithFlatEmbedded(1, new SimpleRecord('foo', 123)),
            $instantiator->instantiate(['id' => '1', 'name' => 'foo', 'value' => '123'], $this->createMock(PlatformInterface::class))
        );
    }

    public function test_with_nested_embedded()
    {
        $instantiator = RecordInstantiator::fromRecordClass(RecordWithNestedEmbedded::class);

        $this->assertSame(['id', 'root_id', 'root_sub_name', 'root_sub_value'], $instantiator->projection());
        $this->assertEquals(
            new RecordWithNestedEmbedded(1, new RecordWithEmbedded(2, new SimpleRecord('foo', 123))),
            $instantiator->instantiate([
                'id' => '1',
                'root_id' => '2',
                'root_sub_name' => 'foo',
                'root_sub_value' => '123',
            ], $this->createMock(PlatformInterface::class))
        );
    }
}

class SimpleRecord
{
    public function __construct(
        public readonly string $name,
        public readonly int $value,
    ) {}
}

class RecordWithNameMapping
{
    public function __construct(
        #[Field('_name')]
        public readonly string $name,
        #[Field('_value')]
        public readonly int $value,
    ) {}
}

class RecordWithDbalType
{
    public function __construct(
        public readonly string $name,
        #[Field(type: TypeInterface::DATETIME)]
        public readonly \DateTime $value,
    ) {}
}

class RecordWithExpression
{
    public function __construct(
        public readonly string $name,
        #[Field(expression: new Raw('foo'))]
        public readonly int $value,
    ) {}
}

class RecordWithCustomProjection
{
    public function __construct(
        #[Field(projection: 't2.jajajaja')]
        public readonly string $name,
        #[Field(projection: false)]
        public readonly int $value,
    ) {}
}

class RecordWithoutConstructor
{
}

class RecordWithEmbedded
{
    public function __construct(
        public readonly int $id,
        #[Embedded]
        public readonly SimpleRecord $sub,
    ) {}
}

class RecordWithFlatEmbedded
{
    public function __construct(
        public readonly int $id,
        #[Embedded('')]
        public readonly SimpleRecord $sub,
    ) {}
}

class RecordWithNestedEmbedded
{
    public function __construct(
        public readonly int $id,
        #[Embedded]
        public readonly RecordWithEmbedded $root,
    ) {}
}
