<?php

namespace Bdf\Prime\Schema\Adapter\Metadata;

use Bdf\Prime\Document;
use Bdf\Prime\Faction;
use Bdf\Prime\Mapper\Metadata;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\PlatformTypesInterface;
use Bdf\Prime\Platform\Sql\Types\SqlStringType;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Types\TypeInterface;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class MetadataColumnTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var PlatformInterface
     */
    private $platform;

    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * @var PlatformTypesInterface
     */
    private $types;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();

        $this->metadata = Document::repository()->metadata();
        $this->platform = $this->prime()->connection('test')->platform();
        $this->types = $this->platform->types();
    }

    /**
     *
     */
    public function test_on_primary()
    {
        $column = new MetadataColumn($this->metadata->attributes['id'], $this->types);

        $this->assertEquals('id_', $column->name());
        $this->assertEquals(new SqlStringType($this->platform, TypeInterface::BIGINT), $column->type());
        $this->assertTrue($column->autoIncrement());
        $this->assertFalse($column->nillable());
        $this->assertNull($column->defaultValue());
        $this->assertNull($column->length());
        $this->assertNull($column->comment());
        $this->assertFalse($column->unsigned());
        $this->assertNull($column->precision());
        $this->assertNull($column->scale());
    }

    /**
     *
     */
    public function test_on_string()
    {
        $column = new MetadataColumn($this->metadata->attributes['uploaderType'], $this->types);

        $this->assertEquals('uploader_type', $column->name());
        $this->assertEquals(TypeInterface::STRING, $column->type()->name());
        $this->assertFalse($column->autoIncrement());
        $this->assertFalse($column->nillable());
        $this->assertNull($column->defaultValue());
        $this->assertEquals(60, $column->length());
        $this->assertNull($column->comment());
        $this->assertFalse($column->unsigned());
        $this->assertNull($column->precision());
        $this->assertNull($column->scale());
    }

    /**
     *
     */
    public function test_on_nillable_string()
    {
        $column = new MetadataColumn($this->metadata->attributes['contact.name'], $this->types);

        $this->assertEquals('contact_name', $column->name());
        $this->assertEquals(TypeInterface::STRING, $column->type()->name());
        $this->assertFalse($column->autoIncrement());
        $this->assertTrue($column->nillable());
        $this->assertNull($column->defaultValue());
        $this->assertEquals(255, $column->length());
        $this->assertNull($column->comment());
    }

    /**
     *
     */
    public function test_with_options()
    {
        $column = new MetadataColumn($this->metadata->attributes['contact.name'] + ['customSchemaOptions' => ['foo' => 'bar']], $this->types);

        $this->assertEquals(['foo' => 'bar'], $column->options());
        $this->assertEquals('bar', $column->option('foo'));
    }

    /**
     *
     */
    public function test_toMetadata()
    {
        $column = new MetadataColumn($this->metadata->attributes['id'], $this->types);

        $this->assertSame($this->metadata->attributes['id'], $column->toMetadata());
    }

    /**
     *
     */
    public function test_defaultValue_must_be_converted_to_db_value()
    {
        $this->metadata = Faction::repository()->mapper()->metadata();

        $column = new MetadataColumn($this->metadata->attributes['enabled'], $this->types);

        $this->assertSame(1, $column->defaultValue());
    }
}
