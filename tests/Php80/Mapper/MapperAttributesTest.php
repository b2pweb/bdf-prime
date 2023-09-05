<?php


namespace Php80\Mapper;

require_once __DIR__.'/_files/entities.php';

use Bdf\Prime\PrimeTestCase;
use Php80\Mapper\_files\CustomCriteria;
use Php80\Mapper\_files\CustomRepository;
use Php80\Mapper\_files\CustomRepositoryEntity;
use Php80\Mapper\_files\ReadonlyEntity;
use Php80\Mapper\_files\WithQuoteIdentifierEntity;
use PHPUnit\Framework\TestCase;

class MapperAttributesTest extends TestCase
{
    use PrimeTestCase;

    protected function setUp(): void
    {
        $this->primeStart();
    }

    protected function tearDown(): void
    {
        $this->primeStop();
    }

    public function test_readonly()
    {
        $this->assertTrue(ReadonlyEntity::repository()->mapper()->isReadOnly());
        $this->assertFalse(ReadonlyEntity::repository()->mapper()->hasSchemaManager());
    }

    public function test_custom_repository()
    {
        $this->assertInstanceOf(CustomRepository::class, CustomRepositoryEntity::repository());
    }

    public function test_useQuoteIdentifier()
    {
        $this->assertTrue(WithQuoteIdentifierEntity::repository()->mapper()->hasQuoteIdentifier());
        $this->assertEquals('SELECT "t0".* FROM "readonly_entity" "t0" WHERE "t0"."id" = ?', WithQuoteIdentifierEntity::where('id', 42)->toSql());
    }

    public function test_criteria_class()
    {
        $this->assertInstanceOf(CustomCriteria::class, CustomRepositoryEntity::repository()->criteria());
    }

    public function test_scope()
    {
        $this->assertEquals('SELECT t0.* FROM readonly_entity t0 WHERE t0.id = ? AND foo = ?', ReadonlyEntity::where('id', 42)->foo('foo')->toSql());
        $this->assertEquals('SELECT t0.* FROM readonly_entity t0 WHERE t0.id = ? AND bar = ?', ReadonlyEntity::where('id', 42)->oof('foo')->toSql());
        $this->assertEquals('SELECT t0.* FROM readonly_entity t0 WHERE t0.id = ? AND baz = ?', ReadonlyEntity::where('id', 42)->baz('foo')->toSql());
    }

    public function test_filters()
    {
        $this->assertEquals("SELECT t0.* FROM readonly_entity t0 WHERE t0.id = 2356372769 OR t0.name = 'foo'", ReadonlyEntity::where('myFilter', 'foo')->toRawSql());
        $this->assertEquals("SELECT t0.* FROM readonly_entity t0 WHERE t0.name = 'acbd18db4cc2f85cedef654fccc4a4d8'", ReadonlyEntity::where('other', 'foo')->toRawSql());
    }

    public function test_repositoryMethod()
    {
        $this->assertSame('foo', ReadonlyEntity::search('aaa'));
        $this->assertSame('bar', ReadonlyEntity::rechercher('aaa'));
    }
}
