<?php

namespace Record;

use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Raw;
use Bdf\Prime\Record\CastType;
use Bdf\Prime\Record\Field;
use Bdf\Prime\Types\TypeInterface;
use PHPUnit\Framework\TestCase;

use function strrev;
use function strtoupper;

class FieldTest extends TestCase
{
    use PrimeTestCase;

    protected function tearDown(): void
    {
        $this->unsetPrime();
    }

    public function test_cast()
    {
        $this->assertSame('123', (new Field())->cast('123'));
        $this->assertSame(null, (new Field())->cast(null));
        $this->assertSame(123, (new Field(castType: CastType::Integer))->cast('123'));
        $this->assertSame(null, (new Field(castType: CastType::Integer))->cast(''));
        $this->assertSame(0, (new Field(castType: CastType::Integer, nullable: false))->cast(''));
        $this->assertSame(null, (new Field(castType: CastType::Integer, nullable: true))->cast(''));
        $this->assertSame('321', (new Field(transformer: strrev(...)))->cast('123'));
        $this->assertSame(321, (new Field(castType: CastType::Integer, transformer: strrev(...)))->cast('123'));
    }

    public function test_empty()
    {
        $field = new Field();

        $this->assertNull($field->name);
        $this->assertNull($field->expression);
        $this->assertNull($field->type);
        $this->assertNull($field->castType);
        $this->assertNull($field->nullable);
        $this->assertNull($field->projection);
    }

    public function test_with_name()
    {
        $field = new Field(
            name: 'original_name',
            expression: 'expression',
            type: 'datetime',
            castType: CastType::String,
            nullable: true,
            projection: 'projection',
        );
        $newField = $field->with(name: 'new_name');

        $this->assertSame('new_name', $newField->name);
        $this->assertSame('expression', $newField->expression);
        $this->assertSame('datetime', $newField->type);
        $this->assertSame(CastType::String, $newField->castType);
        $this->assertTrue($newField->nullable);
        $this->assertSame('projection', $newField->projection);
        $this->assertSame('original_name', $field->name);
    }

    public function test_with_castType()
    {
        $field = new Field(
            name: 'name',
            expression: 'expression',
            type: 'datetime',
            castType: CastType::String,
            nullable: true,
            projection: 'projection',
        );
        $newField = $field->with(castType: CastType::Integer);

        $this->assertSame('name', $newField->name);
        $this->assertSame('expression', $newField->expression);
        $this->assertSame('datetime', $newField->type);
        $this->assertSame(CastType::Integer, $newField->castType);
        $this->assertTrue($newField->nullable);
        $this->assertSame('projection', $newField->projection);
        $this->assertSame(CastType::String, $field->castType);
    }

    public function test_with_type()
    {
        $field = new Field(
            name: 'name',
            expression: 'expression',
            type: 'datetime',
            castType: CastType::String,
            nullable: true,
            projection: 'projection',
        );
        $newField = $field->with(type: 'string');

        $this->assertSame('name', $newField->name);
        $this->assertSame('expression', $newField->expression);
        $this->assertSame('string', $newField->type);
        $this->assertSame(CastType::String, $newField->castType);
        $this->assertTrue($newField->nullable);
        $this->assertSame('projection', $newField->projection);
        $this->assertSame('datetime', $field->type);
    }

    public function test_with_expression()
    {
        $field = new Field(
            name: 'name',
            expression: 'expression',
            type: 'datetime',
            castType: CastType::String,
            nullable: true,
            projection: 'projection',
        );
        $newField = $field->with(expression: 'new_expression');

        $this->assertSame('name', $newField->name);
        $this->assertSame('new_expression', $newField->expression);
        $this->assertSame('datetime', $newField->type);
        $this->assertSame(CastType::String, $newField->castType);
        $this->assertTrue($newField->nullable);
        $this->assertSame('projection', $newField->projection);
        $this->assertSame('expression', $field->expression);
    }

    public function test_with_nullable()
    {
        $field = new Field(
            name: 'name',
            expression: 'expression',
            type: 'datetime',
            castType: CastType::String,
            nullable: true,
            projection: 'projection',
        );
        $newField = $field->with(nullable: false);

        $this->assertSame('name', $newField->name);
        $this->assertSame('expression', $newField->expression);
        $this->assertSame('datetime', $newField->type);
        $this->assertSame(CastType::String, $newField->castType);
        $this->assertFalse($newField->nullable);
        $this->assertSame('projection', $newField->projection);
    }

    public function test_with_projection()
    {
        $field = new Field(
            name: 'name',
            expression: 'expression',
            type: 'datetime',
            castType: CastType::String,
            nullable: true,
            projection: 'projection',
        );
        $newField = $field->with(projection: 'new_projection');

        $this->assertSame('name', $newField->name);
        $this->assertSame('expression', $newField->expression);
        $this->assertSame('datetime', $newField->type);
        $this->assertSame(CastType::String, $newField->castType);
        $this->assertTrue($newField->nullable);
        $this->assertSame('new_projection', $newField->projection);
    }

    public function test_fromReflectionParameter()
    {
        $c = new class {
            public function __construct(
                public $empty = null,
                public string $string = '',
                public ?string $nullableString = null,
                #[Field('other_name')]
                public string $fieldNameMapping = '',
                #[Field(expression: new Raw('expression'))]
                public int $withExpression = 0,
            ) {}
        };
        $r = (new \ReflectionClass($c))->getConstructor();

        $field = Field::fromReflectionParameter($r->getParameters()[0]);
        $this->assertSame('empty', $field->name);
        $this->assertSame(CastType::Mixed, $field->castType);
        $this->assertTrue($field->nullable);
        $this->assertNull($field->projection);
        $this->assertNull($field->expression);
        $this->assertNull($field->type);

        $field = Field::fromReflectionParameter($r->getParameters()[1]);
        $this->assertSame('string', $field->name);
        $this->assertSame(CastType::String, $field->castType);
        $this->assertFalse($field->nullable);
        $this->assertNull($field->projection);
        $this->assertNull($field->expression);
        $this->assertNull($field->type);

        $field = Field::fromReflectionParameter($r->getParameters()[2]);
        $this->assertSame('nullableString', $field->name);
        $this->assertSame(CastType::String, $field->castType);
        $this->assertTrue($field->nullable);
        $this->assertNull($field->projection);
        $this->assertNull($field->expression);
        $this->assertNull($field->type);

        $field = Field::fromReflectionParameter($r->getParameters()[3]);
        $this->assertSame('other_name', $field->name);
        $this->assertSame(CastType::String, $field->castType);
        $this->assertFalse($field->nullable);
        $this->assertNull($field->expression);
        $this->assertNull($field->type);

        $field = Field::fromReflectionParameter($r->getParameters()[4]);
        $this->assertSame('withExpression', $field->name);
        $this->assertSame(CastType::Integer, $field->castType);
        $this->assertFalse($field->nullable);
        $this->assertNull($field->projection);
        $this->assertEquals(new Raw('expression'), $field->expression);
        $this->assertNull($field->type);
    }

    public function test_projection()
    {
        $this->assertSame(['name'], (new Field('name'))->projection());
        $this->assertSame(['alias'], (new Field('name', projection: 'alias'))->projection());
        $this->assertSame([], (new Field('name', projection: false))->projection());
    }

    public function test_projection_with_expression()
    {
        $this->assertEquals(['name' => new Raw('expression')], (new Field('name', expression: new Raw('expression')))->projection());
        $this->assertEquals(['alias' => new Raw('expression')], (new Field('name', expression: new Raw('expression'), projection: 'alias'))->projection());
        $this->assertSame([], (new Field('name', expression: new Raw('expression'), projection: false))->projection());
        $this->assertSame(['name' => 'other_field'], (new Field('name', expression: 'other_field'))->projection());
    }

    public function test_value()
    {
        $platform = $this->createMock(PlatformInterface::class);

        $this->assertSame('bar', (new Field('foo'))->value($platform, ['foo' => 'bar', 'other' => 'baz']));
        $this->assertNull((new Field('foo'))->value($platform, ['other' => 'baz']));
        $this->assertNull((new Field('foo'))->value($platform, ['foo' => null]));
    }

    public function test_value_with_cast()
    {
        $platform = $this->createMock(PlatformInterface::class);

        $this->assertSame(123, (new Field('foo', castType: CastType::Integer))->value($platform, ['foo' => '123']));
        $this->assertNull((new Field('foo', castType: CastType::Integer))->value($platform, []));
        $this->assertSame(0, (new Field('foo', castType: CastType::Integer, nullable: false))->value($platform, []));
    }

    public function test_value_with_transformer()
    {
        $platform = $this->createMock(PlatformInterface::class);

        $this->assertSame('OOF', (new Field('foo', transformer: fn (?string $value) => strtoupper(strrev($value))))->value($platform, ['foo' => 'foo']));
    }

    public function test_value_with_type()
    {
        $this->configurePrime();
        $platform = $this->prime()->connection('test')->platform();

        $this->assertEquals(
            new \DateTime('2025-02-01 15:25:03'),
            (new Field('foo', type: TypeInterface::DATETIME))->value($platform, ['foo' => '2025-02-01 15:25:03'])
        );
        $this->assertNull((new Field('foo', type: TypeInterface::DATETIME))->value($platform, []));
        $this->assertSame(123, (new Field('foo', type: TypeInterface::INTEGER))->value($platform, ['foo' => '123']));
    }

    public function test_value_with_type_and_transformer()
    {
        $this->configurePrime();
        $platform = $this->prime()->connection('test')->platform();

        $this->assertSame(
            '2025-02-01',
            (new Field('foo', type: TypeInterface::DATETIME, transformer: fn (?\DateTimeInterface $date) => $date?->format('Y-m-d')))
                ->value($platform, ['foo' => '2025-02-01 15:25:03'])
        );
    }

    public function test_withAttributesMetadata()
    {
        $field = (new Field('name', castType: CastType::String, nullable: false))->withAttributesMetadata([
            'name' => ['field' => 'name_', 'type' => 'string'],
            'other' => ['field' => 'other_', 'type' => 'integer'],
        ]);

        $this->assertEquals(new Field('name_', type: 'string', castType: CastType::String, nullable: false, projection: 'name'), $field);
    }

    public function test_withAttributesMetadata_with_unknown_attribute()
    {
        $field = (new Field('name', castType: CastType::String, nullable: false))->withAttributesMetadata([
            'other' => ['field' => 'other_', 'type' => 'integer'],
        ]);

        $this->assertEquals(new Field('name', castType: CastType::String, nullable: false, projection: 'name'), $field);
    }

    public function test_withAttributesMetadata_should_keep_explicit_type()
    {
        $field = (new Field('name', type: 'json', castType: CastType::Mixed))->withAttributesMetadata([
            'name' => ['field' => 'name_', 'type' => 'string'],
        ]);

        $this->assertEquals(new Field('name_', type: 'json', castType: CastType::Mixed, projection: 'name'), $field);
    }

    public function test_withAttributesMetadata_with_string_expression()
    {
        $field = (new Field('alias', expression: 'other'))->withAttributesMetadata([
            'alias' => ['field' => 'alias_', 'type' => 'string'],
            'other' => ['field' => 'other_', 'type' => 'integer'],
        ]);

        $this->assertEquals(new Field('alias_', expression: 'other', type: 'integer', projection: 'alias'), $field);
    }

    public function test_withAttributesMetadata_with_object_expression()
    {
        $field = (new Field('alias', expression: new Raw('COUNT(*)')))->withAttributesMetadata([
            'alias' => ['field' => 'alias_', 'type' => 'string'],
        ]);

        $this->assertEquals(new Field('alias_', expression: new Raw('COUNT(*)'), projection: 'alias'), $field);
    }

    /**
     * The projection explicitly defined on the attribute must not be overwritten by the attribute name
     */
    public function test_withAttributesMetadata_should_keep_custom_projection()
    {
        $field = (new Field('name', projection: 't2.name'))->withAttributesMetadata([
            'name' => ['field' => 'name_', 'type' => 'string'],
        ]);

        $this->assertSame('t2.name', $field->projection);
        $this->assertSame(['t2.name'], $field->projection());
    }

    /**
     * A disabled projection must not be re-enabled by the attributes metadata resolution
     */
    public function test_withAttributesMetadata_should_keep_disabled_projection()
    {
        $field = (new Field('name', projection: false))->withAttributesMetadata([
            'name' => ['field' => 'name_', 'type' => 'string'],
        ]);

        $this->assertFalse($field->projection);
        $this->assertSame([], $field->projection());
    }
}
