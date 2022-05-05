<?php

namespace Bdf\Prime\Schema;

use Bdf\Prime\Exception\DBALException;
use Bdf\Prime\Platform\PlatformInterface;
use Bdf\Prime\Platform\Sql\Types\SqlStringType;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Schema\Bag\Column;
use Bdf\Prime\Schema\Bag\IndexSet;
use Bdf\Prime\Schema\Bag\Table;
use Bdf\Prime\Schema\Builder\TypesHelperTableBuilder;
use Doctrine\DBAL\Exception\TableNotFoundException;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class AbstractSchemaManagerTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var AbstractSchemaManager
     */
    protected $schema;

    /**
     * @var PlatformInterface
     */
    protected $platform;

    /**
     * 
     */
    protected function setUp(): void
    {
        $this->primeStart();

        $this->platform = Prime::connection('test')->platform();
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
    public function test_getConnection()
    {
        $connection = Prime::connection('test');

        $this->assertSame($connection, $this->schema->getConnection());
    }
    
    /**
     * 
     */
    public function test_set_get_use_drop()
    {
        $this->assertTrue($this->schema->getUseDrop());
        $this->schema->useDrop(false);
        $this->assertFalse($this->schema->getUseDrop());
    }

    /**
     *
     */
    public function test_simulate_will_not_modify_database()
    {
        Prime::push('Bdf\Prime\TestEntity', [
            'id'   => 1,
            'name' => 'test-name'
        ]);

        $schema = $this->schema->simulate();
        $schema->truncate('test_');
        $this->assertEquals(1, Prime::repository('Bdf\Prime\TestEntity')->count());
    }

    /**
     *
     */
    public function test_simulate_will_create_buffered()
    {
        $schema = $this->schema->simulate();

        $this->assertInstanceOf(SchemaManager::class, $schema);
        $this->assertNotSame($schema, $this->schema);
        $this->assertTrue($schema->isBuffered());
        $this->assertEmpty($schema->pending());

        $schema
            ->drop('test')
            ->truncate('other')
        ;

        $this->assertCount(2, $schema->pending());
    }

    /**
     *
     */
    public function test_simulate_with_closure()
    {
        $parameter = null;

        $schema = $this->schema->simulate(function ($schema) use (&$parameter) {
            $parameter = $schema;

            $this->assertTrue($schema->isBuffered());

            $schema
                ->drop('test')
                ->truncate('other')
            ;
        });

        $this->assertSame($schema, $parameter);
        $this->assertCount(2, $schema->pending());
    }
    
    /**
     * 
     */
    public function test_clear_queries()
    {
        $schema = $this->schema->simulate();

        $schema->drop('test_');
        $schema->clear();
        
        $this->assertEquals([], $schema->toSql());
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
    public function test_change_table()
    {
        $schema = $this->schema->simulate();

        $schema->change('test_', function(TypesHelperTableBuilder $table) {
            $table->text('new');
        });
        
        $this->assertEquals(['ALTER TABLE test_ ADD COLUMN new CLOB NOT NULL'], $schema->toSql());
    }

    /**
     *
     */
    public function test_transaction_will_buffer_operations()
    {
        Prime::push('Bdf\Prime\TestEntity', [
            'id'   => 1,
            'name' => 'test-name'
        ]);

        $isCalled = false;

        $this->schema->transaction(function (SchemaManager $schema) use (&$isCalled) {
            $isCalled = true;

            $this->assertTrue($schema->isBuffered());

            $schema
                ->truncate('test_')
            ;

            $this->assertCount(1, $schema->pending());
            $this->assertEquals(1, Prime::repository('Bdf\Prime\TestEntity')->count());
        });

        $this->assertTrue($isCalled);
        $this->assertFalse($this->schema->isBuffered());
        $this->assertEmpty($this->schema->pending());
        $this->assertEquals(0, Prime::repository('Bdf\Prime\TestEntity')->count());
    }

    /**
     *
     */
    public function test_transaction_on_error_will_not_modify_database()
    {
        Prime::push('Bdf\Prime\TestEntity', [
            'id'   => 1,
            'name' => 'test-name'
        ]);

        try {
            $this->schema->transaction(function (SchemaManager $schema) {
                $schema
                    ->truncate('test_')
                    ->drop('unknown');
            });
        } catch (DBALException $e) {
            $this->assertEquals(1, Prime::repository('Bdf\Prime\TestEntity')->count());
            $this->assertFalse($this->schema->isBuffered());
            $this->assertEmpty($this->schema->pending());

            return;
        }

        $this->fail('An exception should be thrown');
    }

    /**
     *
     */
    public function test_add_unknown_table()
    {
        $manager = $this->schema->simulate();

        $manager->add(new Table(
            'unknown_table_',
            [new Column('col_', new SqlStringType($this->platform), null, 32, false, false, false, false, null, null, null)],
            new IndexSet([])
        ));

        $this->assertEquals([
            'CREATE TABLE unknown_table_ (col_ VARCHAR(32) NOT NULL)'
        ], $manager->pending());
    }

    /**
     *
     */
    public function test_add_without_diff()
    {
        $table = new Table(
            'unknown_table_',
            [new Column('col_', new SqlStringType($this->platform), null, 32, false, false, false, false, null, null, null)],
            new IndexSet([])
        );

        $this->schema->add($table);

        $manager = $this->schema->simulate();

        $manager->add($table);

        $this->assertEmpty($manager->pending());
    }

    /**
     *
     */
    public function test_add_with_diff()
    {
        $table = new Table(
            'unknown_table_',
            [new Column('col_', new SqlStringType($this->platform), null, 32, false, false, false, false, null, null, null)],
            new IndexSet([])
        );

        $this->schema->add($table);

        $manager = $this->schema->simulate();

        $table = new Table(
            'unknown_table_',
            [
                new Column('col_', new SqlStringType($this->platform), null, 32, false, false, false, false, null, null, null),
                new Column('col2_', new SqlStringType($this->platform), null, 32, false, false, false, false, null, null, null)
            ],
            new IndexSet([])
        );

        $manager->add($table);

        $this->assertEquals(['ALTER TABLE unknown_table_ ADD COLUMN col2_ VARCHAR(32) NOT NULL'], $manager->pending());
    }

    /**
     *
     */
    public function test_table()
    {
        $manager = $this->schema->simulate();

        $manager->table('new_table_', function (TypesHelperTableBuilder $builder) {
            $builder
                ->bigint('id_')->primary()
                ->string('name_', 32)
            ;
        });

        $this->assertEquals(['CREATE TABLE new_table_ (id_ BIGINT NOT NULL, name_ VARCHAR(32) NOT NULL, PRIMARY KEY(id_))'], $manager->pending());
    }
}
