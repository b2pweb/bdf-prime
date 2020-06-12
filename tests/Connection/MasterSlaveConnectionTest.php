<?php

namespace Bdf\Prime\Connection;

use Bdf\Prime\Connection\Factory\ConnectionFactory;
use Bdf\Prime\Connection\Factory\MasterSlaveConnectionFactory;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\PrimeTestCase;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class MasterSlaveConnectionTest extends TestCase
{
    use PrimeTestCase;

    /** @var ConnectionRegistry */
    protected $registry;
    /** @var ConnectionManager */
    protected $connections;
    /** @var MasterSlaveConnectionFactory */
    protected $factory;

    /**
     * 
     */
    protected function setUp(): void
    {
        $this->primeStart();

        $configMap = [
            'master-slave' => [
                'adapter' => 'sqlite',
                'memory'  => true,
                'dbname'  => 'TEST',
                'read'    => [
                    'dbname'  => 'TEST_READ',
                ]
            ],
        ];

        $this->factory = new MasterSlaveConnectionFactory(new ConnectionFactory());
        $this->registry = new ConnectionRegistry($configMap, $this->factory);
        $this->connections = new ConnectionManager($this->registry);
        $master = $this->connections->getConnection('master-slave');
        $master->schema()
            ->table('test', function($table) {
                $table->bigint('id', true);
                $table->string('name');
                $table->primary('id');
            });
        $master->getReadConnection()->schema()
            ->table('test', function($table) {
                $table->bigint('id', true);
                $table->string('name');
                $table->primary('id');
            });
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
    public function test_support()
    {
        $this->assertFalse($this->factory->support('foo', []));
        $this->assertTrue($this->factory->support('foo', ['read' => []]));
    }

    /**
     *
     */
    public function test_wrapper()
    {
        $this->assertEquals(MasterSlaveConnection::class, get_class($this->connections->getConnection('master-slave')));
    }

    /**
     *
     */
    public function test_read_connection_wrapper()
    {
        $this->assertEquals(SimpleConnection::class, get_class($this->connections->getConnection('master-slave')->getReadConnection()));
    }

    /**
     *
     */
    public function test_unknown_sub_connection()
    {
        $this->expectException('LogicException');

        $this->connections->getConnection('master-slave')->getConnection('unknown');
    }

    /**
     *
     */
    public function test_sub_connection_interface()
    {
        $master = $this->connections->getConnection('master-slave');

        $this->assertEquals($master->getReadConnection(), $master->getConnection('read'));
        $this->assertEquals($master, $master->getConnection('master'));
    }

    /**
     *
     */
    public function test_connection_manager_access()
    {
        $master = $this->connections->getConnection('master-slave.master');
        $this->assertEquals('master-slave', $master->getName());

        $read = $this->connections->getConnection('master-slave.read');
        $this->assertEquals('master-slave.read', $read->getName());
    }

    /**
     *
     */
    public function test_quote()
    {
        $master = $this->connections->getConnection('master-slave');

        $this->assertEquals('\'f"f\'', $master->quote('f"f'));
    }

    /**
     * @group master-slave
     * 
     * Tester que les ecritures partent sur le master, et les lectures sur le slave
     * Le principe consiste à enregistrer une ligne dans une table non repliquée sur le master et sur le slave
     * 
     * On update la ligne via la connection master/slave et on verifie que l'update a été pris en compte sur le master et non sur le slave
     * 
     * On test ensuite une lecture devant retourner la valeur de la ligne du slave
     */
    public function test_master_slave()
    {
        /** @var MasterSlaveConnection $connection */
        $master   = $this->connections->getConnection('master-slave');
        $slave    = $master->getReadConnection();

        //testing data
        $slave->insert('test', [
            'id'   => 10,
            'name' => 'slave',
        ]);
        $master->insert('test', [
            'id'   => 10,
            'name' => 'slave',
        ]);
        
        //mise a jour de la ligne devant etre fait sur le master uniquement
        $master->from('test')->update([
            'name' => 'master',
        ]);
        
        //Ecriture part sur le master
        $row = $master->force()->from('test')->where('id', 10)->first();
        $this->assertEquals('master', $row['name']);
        $row = $slave->from('test')->where('id', 10)->first();
        $this->assertEquals('slave', $row['name']);
        
        //Lecture part sur le slave
        $row = $master->from('test')->where('id', 10)->first();
        $this->assertEquals('slave', $row['name']);
    }
}