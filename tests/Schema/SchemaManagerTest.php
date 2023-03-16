<?php

namespace Bdf\Prime\Schema;

use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Schema\Builder\TypesHelperTableBuilder;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class SchemaManagerTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var SchemaManager
     */
    protected $schema;
    
    /**
     * 
     */
    protected function setUp(): void
    {
        $this->primeStart();

        $this->schema = new SchemaManager(Prime::connection('test'));
    }

    /**
     * 
     */
    protected function declareTestData($pack)
    {
        $pack->declareEntity([
            'Bdf\Prime\TestEntity',
        ]);
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->primeStop();
    }
    
    /**
     * 
     */
    public function test_doctrine_schema_manager()
    {
        $this->assertInstanceOf('Doctrine\DBAL\Schema\AbstractSchemaManager', $this->schema->getDoctrineManager());
    }

    /**
     * 
     */
//    public function test_has_database()
//    {
//        $this->assertFalse($this->schema->hasDatabase('unknow'));
//        $this->assertTrue($this->schema->hasDatabase('test'));
//    }
    
    /**
     * 
     */
    public function test_load_tables()
    {
        $schema = $this->schema->loadSchema();

        $this->assertTrue($schema->hasTable('test_'));
    }

    /**
     *
     */
    public function test_has_table()
    {
        $this->assertFalse($this->schema->has('unknow'));
        $this->assertTrue($this->schema->has('test_'));
    }

    /**
     * 
     */
    public function test_load_unknown_table()
    {
        $table = $this->schema->load('unknow');
        
        $this->assertEquals('unknow', $table->name());
        $this->assertEquals([], $table->columns());
    }
    
    /**
     * 
     */
    public function test_load_table()
    {
        $table = $this->schema->load('test_');
        
        $this->assertEquals('test_', $table->name());
        $this->assertInstanceOf(ColumnInterface::class, $table->column('id'));
        $this->assertInstanceOf(ColumnInterface::class, $table->column('name'));
    }
    
    /**
     * 
     */
    public function test_rename_sql()
    {
        $schema = $this->schema->simulate();
        $schema->generateRollback(true);
        $schema->rename('test_', 'test');
        
        $this->assertEquals(['ALTER TABLE test_ RENAME TO test'], $schema->toSql());
        $this->assertEquals(['ALTER TABLE test RENAME TO test_'], $schema->rollbackQueries());
    }
    
    /**
     * 
     */
    public function test_rename()
    {
        $this->schema->rename('test_', 'test');
        
        $this->assertTrue($this->schema->has('test'));
    }
    
    /**
     * 
     */
    public function test_drop_sql()
    {
        $schema = $this->schema->simulate();
        $schema->generateRollback(true);
        $schema->drop('test_');
        
        $this->assertEquals(['DROP TABLE test_'], $schema->toSql());
        $this->assertEquals(['CREATE TABLE test_ (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL COLLATE "BINARY", foreign_key INTEGER DEFAULT NULL, date_insert DATETIME DEFAULT NULL)'], $schema->rollbackQueries());
    }
    
    /**
     * 
     */
    public function test_drop()
    {
        $this->schema->drop('test_');
        
        $this->assertFalse($this->schema->has('test_'));
    }
    
    /**
     * 
     */
    public function test_truncate_sql()
    {
        $schema = $this->schema->simulate();
        $schema->truncate('test_');
        
        $this->assertEquals(['DELETE FROM test_'], $schema->toSql());
    }
    
    /**
     * 
     */
    public function test_truncate()
    {
        Prime::push('Bdf\Prime\TestEntity', [
            'id'   => 1,
            'name' => 'test-name'
        ]);
        
        $this->assertEquals(1, Prime::repository('Bdf\Prime\TestEntity')->count());
        
        $this->schema->truncate('test_');
        
        $this->assertEquals(0, Prime::repository('Bdf\Prime\TestEntity')->count());
    }

    /**
     * #16653 : Default value is not properly converted to db value
     */
    public function test_diff_with_boolean_with_defautlValue()
    {
        $this->schema->table('table_with_boolean', function (TypesHelperTableBuilder $builder) {
            $builder
                ->integer('id')->autoincrement()
                ->boolean('value', false)
            ;
        });

        $schema = $this->schema->simulate();

        $schema->change('table_with_boolean', function (TypesHelperTableBuilder $builder) {
            $builder
                ->integer('id')->autoincrement()
                ->boolean('value', false)
            ;
        });

        $this->assertEmpty($schema->pending());
    }
}
