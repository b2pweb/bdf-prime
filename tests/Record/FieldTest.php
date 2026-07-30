<?php

namespace Record;

use Bdf\Prime\Query\Expression\Raw;
use Bdf\Prime\Record\CastType;
use Bdf\Prime\Record\Field;
use PHPUnit\Framework\TestCase;

use function strrev;

class FieldTest extends TestCase
{
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
}
