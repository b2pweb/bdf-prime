<?php

namespace Bdf\Prime\Schema;

use Bdf\Prime\CrossConnectionSequenceEntity;
use Bdf\Prime\Customer;
use Bdf\Prime\EntityWithIndex;
use Bdf\Prime\Faction;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Schema\Adapter\Metadata\MetadataTable;
use Bdf\Prime\Types\TypeInterface;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ResolverTest extends TestCase
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
    protected function tearDown(): void
    {
        $this->primeStop();
    }
    
    /**
     *
     */
    public function test_table()
    {
        $table = Customer::repository()->schema()->table();

        $this->assertInstanceOf(MetadataTable::class, $table);

        $this->assertEquals('customer_', $table->name());
        $this->assertCount(3, $table->columns());

        $this->assertEquals(TypeInterface::BIGINT, $table->column('id_')->type()->name());
        $this->assertEquals(TypeInterface::BIGINT, $table->column('parent_id')->type()->name());
        $this->assertTrue($table->column('parent_id')->nillable());
        $this->assertEquals(TypeInterface::STRING, $table->column('name_')->type()->name());

        $this->assertEquals(['id_'], $table->indexes()->primary()->fields());
    }

    /**
     *
     */
    public function test_table_with_foreign_keys()
    {
        $table = User::repository()->schema()->table(true);

        $this->assertInstanceOf(TableInterface::class, $table);

        $this->assertEquals('user_', $table->name());
        $this->assertCount(5, $table->columns());

        $constraints = $table->constraints();

        $this->assertCount(2, $constraints->all());

        $this->assertEquals('customer_', $constraints->get('customer')->table());
        $this->assertEquals(['customer_id'], $constraints->get('customer')->fields());
        $this->assertEquals(['id_'], $constraints->get('customer')->referred());

        $this->assertEquals('faction_', $constraints->get('faction')->table());
        $this->assertEquals(['faction_id'], $constraints->get('faction')->fields());
        $this->assertEquals(['id_'], $constraints->get('faction')->referred());
    }

    /**
     *
     */
    public function test_sequence_with_sequence()
    {
        $table = Customer::repository()->schema()->sequence();

        $this->assertInstanceOf(TableInterface::class, $table);

        $this->assertEquals('customer_seq_', $table->name());
        $this->assertCount(1, $table->columns());

        $this->assertEquals(TypeInterface::BIGINT, $table->column('id')->type()->name());
        $this->assertEquals(['id'], $table->indexes()->primary()->fields());
    }

    /**
     *
     */
    public function test_sequence_without_sequence()
    {
        $table = Faction::repository()->schema()->sequence();

        $this->assertNull($table);
    }

    /**
     *
     */
    public function test_functional_drop()
    {
        $resolver = Customer::repository()->schema();
        $resolver->migrate();

        $manager = $this->prime()->connection('test')->schema();

        $this->assertTrue($manager->hasTable('customer_'));
        $this->assertTrue($manager->hasTable('customer_seq_'));

        $resolver->drop();

        $this->assertFalse($manager->hasTable('customer_'));
        $this->assertFalse($manager->hasTable('customer_seq_'));
    }

    /**
     *
     */
    public function test_functional_diff_table_not_present()
    {
        $resolver = Customer::repository()->schema();
        $resolver->drop();

        $this->assertEquals([
            'CREATE TABLE customer_ (id_ BIGINT NOT NULL, parent_id BIGINT DEFAULT NULL, name_ VARCHAR(255) NOT NULL, PRIMARY KEY(id_))',
            'CREATE TABLE customer_seq_ (id BIGINT NOT NULL, PRIMARY KEY(id))'
        ], $resolver->diff());
    }

    /**
     *
     */
    public function test_functional_migrate_with_cross_connection_sequence()
    {
        $this->prime()->connections()->addConnection('sequence', [
            'adapter' => 'sqlite',
            'memory' => true
        ]);

        $resolver = CrossConnectionSequenceEntity::repository()->schema();
        $resolver->migrate();

        $this->assertTrue($this->prime()->connection('test')->schema()->hasTable('test_cross_connection_'));
        $this->assertFalse($this->prime()->connection('test')->schema()->hasTable('test_cross_connection_seq_'));

        $this->assertFalse($this->prime()->connection('sequence')->schema()->hasTable('test_cross_connection_'));
        $this->assertTrue($this->prime()->connection('sequence')->schema()->hasTable('test_cross_connection_seq_'));

        $this->assertEquals([['id' => 0]], $this->prime()->connection('sequence')->from('test_cross_connection_seq_')->all());

        $resolver->drop();
        $this->assertFalse($this->prime()->connection('test')->schema()->hasTable('test_cross_connection_'));
        $this->assertFalse($this->prime()->connection('sequence')->schema()->hasTable('test_cross_connection_seq_'));
    }

    /**
     * @see http://redmine.b2pweb.com/issues/18183
     */
    public function test_diff_with_legacy_indexes_format_without_diff_should_return_nothing()
    {
        $resolver = EntityWithIndex::repository()->schema();
        $resolver->migrate();
        $this->assertEmpty($resolver->diff());
    }
}
