<?php

namespace Bus\Command;

use Bdf\Prime\Bus\Command\TransactionManager;
use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\PrimeTestCase;
use PHPUnit\Framework\TestCase;

class TransactionManagerTest extends TestCase
{
    use PrimeTestCase;

    protected function setUp(): void
    {
        $this->configurePrime();
        $this->prime()->connections()->declareConnection('other', 'sqlite::memory:');


        $this->prime()->connection('test')->executeStatement('CREATE TABLE foo (id INTEGER PRIMARY KEY, name TEXT)');
        $this->prime()->connection('other')->executeStatement('CREATE TABLE foo (id INTEGER PRIMARY KEY, name TEXT)');
    }

    protected function tearDown(): void
    {
        $this->unsetPrime();
    }

    public function test_empty()
    {
        $this->expectNotToPerformAssertions();

        $manager = new TransactionManager();

        $manager->commit();
        $manager->rollback();
    }

    public function test_start_commit()
    {
        $manager = new TransactionManager();

        $this->assertTrue($manager->start($this->prime()->connection('test')));
        $this->assertTrue($this->prime()->connection('test')->isTransactionActive());
        $this->assertFalse($this->prime()->connection('other')->isTransactionActive());

        $this->assertTrue($manager->start($this->prime()->connection('other')));
        $this->assertTrue($this->prime()->connection('test')->isTransactionActive());
        $this->assertTrue($this->prime()->connection('other')->isTransactionActive());

        $this->assertTrue($manager->start($this->prime()->connection('test')));

        $this->prime()->connection('test')->insert('foo', ['id' => 1, 'name' => 'test']);
        $this->prime()->connection('other')->insert('foo', ['id' => 1, 'name' => 'abcd']);

        $manager->commit();

        $this->assertFalse($this->prime()->connection('test')->isTransactionActive());
        $this->assertFalse($this->prime()->connection('other')->isTransactionActive());

        $this->assertEquals([['id' => 1, 'name' => 'test']], $this->prime()->connection('test')->from('foo')->all());
        $this->assertEquals([['id' => 1, 'name' => 'abcd']], $this->prime()->connection('other')->from('foo')->all());
    }

    public function test_start_rollback()
    {
        $manager = new TransactionManager();

        $this->assertTrue($manager->start($this->prime()->connection('test')));
        $this->assertTrue($this->prime()->connection('test')->isTransactionActive());
        $this->assertFalse($this->prime()->connection('other')->isTransactionActive());

        $this->assertTrue($manager->start($this->prime()->connection('other')));
        $this->assertTrue($this->prime()->connection('test')->isTransactionActive());
        $this->assertTrue($this->prime()->connection('other')->isTransactionActive());

        $this->assertTrue($manager->start($this->prime()->connection('test')));

        $this->prime()->connection('test')->insert('foo', ['id' => 1, 'name' => 'test']);
        $this->prime()->connection('other')->insert('foo', ['id' => 1, 'name' => 'abcd']);

        $manager->rollback();

        $this->assertFalse($this->prime()->connection('test')->isTransactionActive());
        $this->assertFalse($this->prime()->connection('other')->isTransactionActive());

        $this->assertEquals([], $this->prime()->connection('test')->from('foo')->all());
        $this->assertEquals([], $this->prime()->connection('other')->from('foo')->all());
    }

    public function test_start_transaction_not_supported()
    {
        $conn = $this->createMock(ConnectionInterface::class);
        $this->assertFalse((new TransactionManager())->start($conn));
    }

    public function test_destruct_should_rollback()
    {
        $manager = new TransactionManager();

        $this->assertTrue($manager->start($this->prime()->connection('test')));
        $this->assertTrue($manager->start($this->prime()->connection('other')));

        $this->prime()->connection('test')->insert('foo', ['id' => 1, 'name' => 'test']);
        $this->prime()->connection('other')->insert('foo', ['id' => 1, 'name' => 'abcd']);

        unset($manager);

        $this->assertFalse($this->prime()->connection('test')->isTransactionActive());
        $this->assertFalse($this->prime()->connection('other')->isTransactionActive());

        $this->assertEquals([], $this->prime()->connection('test')->from('foo')->all());
        $this->assertEquals([], $this->prime()->connection('other')->from('foo')->all());
    }
}
