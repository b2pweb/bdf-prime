<?php

namespace Bdf\Prime\Repository\Write;

use Bdf\Prime\CompositePkEntity;
use Bdf\Prime\Customer;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Test\RepositoryAssertion;
use Bdf\Prime\TestEntity;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class BufferedWriterTest extends TestCase
{
    use PrimeTestCase;
    use RepositoryAssertion;

    /**
     * @var User
     */
    private $basicUser;

    /**
     * @var BufferedWriter
     */
    private $writer;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();

        $this->basicUser = new User([
            'id'            => '1',
            'name'          => 'TEST1',
            'customer'      => new Customer(['id' => '1']),
            'dateInsert'    => new \DateTime(),
            'roles'         => ['2']
        ]);

        $this->writer = new BufferedWriter(User::repository(), new Writer(User::repository(), $this->prime()));
    }

    /**
     *
     */
    protected function declareTestData($pack)
    {
        $pack->declareEntity([User::class, TestEntity::class, CompositePkEntity::class]);
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->primeStop();
        User::repository()->detachAll();
    }

    /**
     *
     */
    public function test_insert()
    {
        $this->assertEquals(1, $this->writer->insert($this->basicUser));
        $this->assertNull(User::refresh($this->basicUser));
        $this->assertEquals(1, $this->writer->pending());

        $this->assertEquals(1, $this->writer->flush());
        $this->assertEntity($this->basicUser, User::refresh($this->basicUser));
        $this->assertEquals(0, $this->writer->pending());
    }

    /**
     *
     */
    public function test_update()
    {
        $this->pack()->nonPersist($this->basicUser);

        $this->basicUser->roles = ['2', '3'];

        $this->assertEquals(1, $this->writer->update($this->basicUser));
        $this->assertEquals(['2'], User::refresh($this->basicUser)->roles);
        $this->assertEquals(1, $this->writer->pending());

        $this->assertEquals(1, $this->writer->flush());
        $this->assertEntity($this->basicUser, User::refresh($this->basicUser));
        $this->assertEquals(['2', '3'], User::refresh($this->basicUser)->roles);
        $this->assertEquals(0, $this->writer->pending());
    }

    /**
     *
     */
    public function test_flush_noop()
    {
        $this->assertEquals(0, $this->writer->flush());
    }

    /**
     *
     */
    public function test_multi_operations()
    {
        $this->writer->insert($this->basicUser);

        $user = clone $this->basicUser;
        $user->roles = ['2', '3'];

        $this->writer->update($user);

        $this->assertNull(User::refresh($this->basicUser));
        $this->assertEquals(2, $this->writer->pending());

        $this->assertEquals(2, $this->writer->flush());
        $this->assertEquals(['2', '3'], User::refresh($this->basicUser)->roles);
        $this->assertEquals(0, $this->writer->pending());
    }

    /**
     *
     */
    public function test_delete_simple()
    {
        $this->pack()->nonPersist($this->basicUser);

        $this->assertEquals(1, $this->writer->delete($this->basicUser));
        $this->assertNotNull(User::refresh($this->basicUser));
        $this->assertEquals(1, $this->writer->pending());

        $this->assertEquals(1, $this->writer->flush());
        $this->assertEquals(0, $this->writer->pending());
        $this->assertNull(User::refresh($this->basicUser));
    }

    /**
     *
     */
    public function test_delete_bulk()
    {
        $users = [];

        for ($i = 1; $i < 10; ++$i) {
            $users[] = new User([
                'id' => $i,
                'name' => 'user_'.$i,
                'roles' => ['2'],
                'customer' => new Customer(['id' => '1'])
            ]);
        }

        $this->pack()->nonPersist($users);

        foreach ($users as $user) {
            $this->writer->delete($user);
        }

        $this->assertEquals(9, $this->writer->pending());
        $this->assertEquals(9, $this->writer->flush());
        $this->assertEquals(0, $this->writer->pending());

        $this->assertEmpty(User::all());
    }

    /**
     *
     */
    public function test_delete_bulk_skip_by_event()
    {
        User::repository()->deleting(function () { return false; });

        $users = [];

        for ($i = 1; $i < 10; ++$i) {
            $users[] = new User([
                'id' => $i.'',
                'name' => 'user_'.$i,
                'roles' => ['2'],
                'customer' => new Customer(['id' => '1'])
            ]);
        }

        $this->pack()->nonPersist($users);

        foreach ($users as $user) {
            $this->writer->delete($user);
        }

        $this->assertEquals(9, $this->writer->pending());
        $this->assertEquals(0, $this->writer->flush());
        $this->assertEquals(0, $this->writer->pending());
        $this->assertEntities($users, User::all());
    }

    /**
     *
     */
    public function test_clear()
    {
        $this->assertEquals(1, $this->writer->insert($this->basicUser));
        $this->assertEquals(1, $this->writer->pending());

        $this->writer->clear();
        $this->assertEquals(0, $this->writer->pending());
    }

    /**
     *
     */
    public function test_delete_with_composite_pk()
    {
        $this->pack()->nonPersist([
            $c1 = new CompositePkEntity(['key1' => 'a', 'key2' => 'b']),
            $c2 = new CompositePkEntity(['key1' => 'c', 'key2' => 'd']),
            $c3 = new CompositePkEntity(['key1' => 'c', 'key2' => 'b']),
        ]);

        $writer = new BufferedWriter(CompositePkEntity::repository());

        $writer->delete($c1);
        $writer->delete($c2);

        $this->assertEquals(2, $writer->flush());

        $this->assertEquals(1, CompositePkEntity::count());
        $this->assertEntities([$c3], CompositePkEntity::all());
    }
}
