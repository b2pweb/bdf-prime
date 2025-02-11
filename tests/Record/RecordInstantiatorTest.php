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
            'name' => new Field('name', castType: CastType::String, nullable: false),
            'value' => new Field('value', castType: CastType::Integer, nullable: false),
        ], $instantiator->fields);

        $this->assertSame(['name', 'value'], $instantiator->projection());
        $this->assertEquals(new SimpleRecord('foo', 123), $instantiator->instantiate(['name' => 'foo', 'value' => 123], $this->createMock(PlatformInterface::class)));
    }

    public function test_name_mapping()
    {
        $instantiator = RecordInstantiator::fromRecordClass(RecordWithNameMapping::class);

        $this->assertSame(RecordWithNameMapping::class, $instantiator->recordClass);
        $this->assertEquals([
            'name' => new Field('_name', castType: CastType::String, nullable: false),
            'value' => new Field('_value', castType: CastType::Integer, nullable: false),
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
            'name' => new Field('name', castType: CastType::String, nullable: false),
            'value' => new Field('value', type: 'datetime', castType: CastType::Mixed, nullable: false),
        ], $instantiator->fields);

        $this->assertSame(['name', 'value'], $instantiator->projection());
        $this->assertEquals(new RecordWithDbalType('foo', new \DateTime('2025-02-01T15:25:03')), $instantiator->instantiate(['name' => 'foo', 'value' => '2025-02-01 15:25:03'], $platform));
    }

    public function test_with_expression()
    {
        $instantiator = RecordInstantiator::fromRecordClass(RecordWithExpression::class);

        $this->assertSame(RecordWithExpression::class, $instantiator->recordClass);
        $this->assertEquals([
            'name' => new Field('name', castType: CastType::String, nullable: false),
            'value' => new Field('value', expression: new Raw('foo'), castType: CastType::Integer, nullable: false),
        ], $instantiator->fields);

        $this->assertEquals(['name', 'value' => new Raw('foo')], $instantiator->projection());
        $this->assertEquals(new RecordWithExpression('foo', 123), $instantiator->instantiate(['name' => 'foo', 'value' => '123'], $this->createMock(PlatformInterface::class)));
    }

    public function test_with_projection()
    {
        $instantiator = RecordInstantiator::fromRecordClass(RecordWithCustomProjection::class);

        $this->assertSame(RecordWithCustomProjection::class, $instantiator->recordClass);
        $this->assertEquals([
            'name' => new Field('name', castType: CastType::String, nullable: false, projection: 't2.jajajaja'),
            'value' => new Field('value', castType: CastType::Integer, nullable: false, projection: false),
        ], $instantiator->fields);

        $this->assertEquals(['t2.jajajaja'], $instantiator->projection());
        $this->assertEquals(new RecordWithCustomProjection('foo', 123), $instantiator->instantiate(['name' => 'foo', 'value' => '123'], $this->createMock(PlatformInterface::class)));
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
