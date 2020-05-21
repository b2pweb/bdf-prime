<?php

namespace Bdf\Prime\Sharding;

require_once __DIR__.'/../Repository/Write/WriterTest.php';

use Bdf\Prime\Connection\SimpleConnection;
use Bdf\Prime\Customer;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Repository\Write\Writer;
use Bdf\Prime\TestEntity;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class ShardingWriterTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var Writer
     */
    private $writer;

    /**
     * @var User
     */
    private $basicUser;

    /**
     * @var SimpleConnection
     */
    private $shard1;

    /**
     * @var SimpleConnection
     */
    private $shard2;


    /**
     *
     */
    protected function setUp(): void
    {
        $this->configurePrime();

        $this->prime()->connections()->removeConnection('test');
        $this->prime()->connections()->addConnection('test', [
            'adapter'           => 'sqlite',
            'memory'            => true,
            'dbname'            => 'TEST',
            'distributionKey'   => 'id',
            'shards'    => [
                'shard1' => ['dbname'  => 'TEST_SHARD1'],
                'shard2' => ['dbname'  => 'TEST_SHARD2'],
            ]
        ]);

        $this->basicUser = new User([
            'id'            => '1',
            'name'          => 'TEST1',
            'customer'      => new Customer(['id' => '1']),
            'dateInsert'    => new \DateTime(),
            'roles'         => ['2']
        ]);

        $this->writer = new Writer(User::repository(), $this->prime());

        $this->pack()
            ->declareEntity([User::class, TestEntity::class])
            ->initialize()
        ;

        $this->shard1 = $this->prime()->connection('test')->getShardConnection('shard1');
        $this->shard2 = $this->prime()->connection('test')->getShardConnection('shard2');
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->primeStop();
        $this->unsetPrime();
    }

    /**
     *
     */
    public function test_insert()
    {
        $this->assertEquals(1, $this->writer->insert($this->basicUser));

        $this->assertEquals($this->basicUser, User::refresh($this->basicUser));

        $this->assertCount(0, $this->shard1->from('user_')->all());
        $this->assertCount(1, $this->shard2->from('user_')->all());
    }

    /**
     *
     */
    public function test_insert_ignore()
    {
        $this->writer->insert($this->basicUser);

        $newUser = clone $this->basicUser;
        $newUser->name = 'new name';

        $this->assertEquals(0, $this->writer->insert($this->basicUser, ['ignore' => true]));
        $this->assertNotEquals($newUser, User::refresh($this->basicUser));
        $this->assertEquals($this->basicUser, User::refresh($this->basicUser));
    }

    /**
     *
     */
    public function test_insert_multiple()
    {
        for ($i = 1; $i <= 100; ++$i) {
            $this->writer->insert(new User([
                'id'         => $i,
                'name'       => 'user_'.$i,
                'customer'   => new Customer(['id' => '1']),
                'dateInsert' => new \DateTime(),
                'roles'      => ['2'],
            ]));
        }

        $this->assertCount(50, $this->shard1->from('user_')->all());
        $this->assertCount(50, $this->shard2->from('user_')->all());
    }

    /**
     *
     */
    public function test_update()
    {
        $user = new User([
            'id'         => 4,
            'name'       => 'user_4',
            'customer'   => new Customer(['id' => '1']),
            'dateInsert' => new \DateTime(),
            'roles'      => ['2'],
        ]);

        $this->writer->insert($user);
        $this->writer->insert($this->basicUser);

        $user->name = 'new name';

        $this->assertEquals(1, $this->writer->update($user));
        $this->assertEquals('new name', User::refresh($user)->name);

        $this->basicUser->name = 'other name';
        $this->assertEquals(1, $this->writer->update($this->basicUser));
        $this->assertEquals('other name', User::refresh($this->basicUser)->name);
    }

    /**
     *
     */
    public function test_update_not_found()
    {
        $user = new User([
            'id'         => 4,
            'name'       => 'user_4',
            'customer'   => new Customer(['id' => '1']),
            'dateInsert' => new \DateTime(),
            'roles'      => ['2'],
        ]);

        $this->assertEquals(0, $this->writer->update($user));
    }

    /**
     *
     */
    public function test_delete()
    {
        $user = new User([
            'id'         => 4,
            'name'       => 'user_4',
            'customer'   => new Customer(['id' => '1']),
            'dateInsert' => new \DateTime(),
            'roles'      => ['2'],
        ]);

        $this->writer->insert($this->basicUser);
        $this->writer->insert($user);

        $this->assertEquals(1, $this->writer->delete($this->basicUser));
        $this->assertNull(User::refresh($this->basicUser));
        $this->assertCount(1, $this->shard1->from('user_')->all());
        $this->assertCount(0, $this->shard2->from('user_')->all());

        $this->assertEquals(1, $this->writer->delete($user));
        $this->assertNull(User::refresh($user));
        $this->assertCount(0, $this->shard1->from('user_')->all());
        $this->assertCount(0, $this->shard2->from('user_')->all());

        $this->assertEquals(0, $this->writer->delete($this->basicUser));
        $this->assertEquals(0, $this->writer->delete($user));
    }
}
