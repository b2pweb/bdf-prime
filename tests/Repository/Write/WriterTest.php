<?php

namespace Bdf\Prime\Repository\Write;

use Bdf\Prime\Customer;
use Bdf\Prime\Exception\DBALException;
use Bdf\Prime\Faction;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Test\RepositoryAssertion;
use Bdf\Prime\TestEntity;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;

/**
 * Class WriterTest
 */
class WriterTest extends TestCase
{
    use PrimeTestCase;
    use RepositoryAssertion;

    /**
     * @var User
     */
    private $basicUser;

    /**
     * @var Writer
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

        $this->writer = new Writer(User::repository(), $this->prime());
    }

    /**
     *
     */
    protected function declareTestData($pack)
    {
        $pack->declareEntity([User::class, TestEntity::class]);
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
        $this->assertEntity($this->basicUser, User::refresh($this->basicUser));
    }

    /**
     *
     */
    public function test_insert_autoincrement_id()
    {
        $entity = new TestEntity(['name' => 'test']);
        $writer = new Writer(TestEntity::repository(), $this->prime());

        $this->assertEquals(1, $writer->insert($entity));
        $this->assertNotNull(TestEntity::refresh($entity));
        $this->assertEquals(1, $entity->id);
    }

    /**
     *
     */
    public function test_insert_skip_with_event()
    {
        User::repository()->inserting(function () { return false; });

        $this->assertEquals(0, $this->writer->insert($this->basicUser));
        $this->assertNull(User::refresh($this->basicUser));
    }

    /**
     *
     */
    public function test_insert_twice_failed()
    {
        $this->expectException(DBALException::class);

        $this->writer->insert($this->basicUser);
        $this->writer->insert($this->basicUser);
    }

    /**
     *
     */
    public function test_insert_twice_ignore()
    {
        $this->writer->insert($this->basicUser);

        $this->basicUser->roles = ['2', '3'];
        $this->assertEquals(0, $this->writer->insert($this->basicUser, ['ignore' => true]));
        $this->assertEquals(['2'], User::refresh($this->basicUser)->roles);
    }

    /**
     *
     */
    public function test_update_not_found()
    {
        $this->assertEquals(0, $this->writer->update($this->basicUser));
    }

    /**
     *
     */
    public function test_update_success()
    {
        $this->pack()->nonPersist($this->basicUser);

        $this->basicUser->roles = ['2', '3'];

        $this->assertEquals(1, $this->writer->update($this->basicUser));
        $this->assertEquals(['2', '3'], User::refresh($this->basicUser)->roles);
    }

    /**
     *
     */
    public function test_update_attributes_option()
    {
        $this->pack()->nonPersist($this->basicUser);

        $this->basicUser->roles = ['2', '3'];
        $this->basicUser->name = 'new name';

        $this->assertEquals(1, $this->writer->update($this->basicUser, ['attributes' => ['name']]));
        $this->assertEquals(['2'], User::refresh($this->basicUser)->roles);
        $this->assertEquals('new name', User::refresh($this->basicUser)->name);
    }

    /**
     *
     */
    public function test_update_skip_with_event()
    {
        $this->pack()->nonPersist($this->basicUser);

        User::repository()->updating(function () { return false; });

        $this->basicUser->roles = ['2', '3'];

        $this->assertEquals(0, $this->writer->update($this->basicUser));
        $this->assertEquals(['2'], User::refresh($this->basicUser)->roles);
    }

    /**
     *
     */
    public function test_update_ignore_primary_attributes()
    {
        $this->pack()->nonPersist($this->basicUser);

        $this->basicUser->roles = ['2', '3'];

        $this->assertEquals(0, $this->writer->update($this->basicUser, ['attributes' => ['id']]));
        $this->assertEquals(['2'], User::refresh($this->basicUser)->roles);
    }

    /**
     *
     */
    public function test_delete_not_found()
    {
        $this->assertEquals(0, $this->writer->delete($this->basicUser));
    }

    /**
     *
     */
    public function test_delete_success()
    {
        $this->pack()->nonPersist($this->basicUser);

        $this->assertEquals(1, $this->writer->delete($this->basicUser));
        $this->assertNull(User::refresh($this->basicUser));
    }

    /**
     *
     */
    public function test_delete_skip_with_event()
    {
        User::repository()->deleting(function () { return false; });
        $this->pack()->nonPersist($this->basicUser);

        $this->assertEquals(0, $this->writer->delete($this->basicUser));
        $this->assertNotNull(User::refresh($this->basicUser));
    }

    /**
     *
     */
    public function test_writer_with_constaints()
    {
        $writer = new Writer(Faction::repository(), $this->prime());

        $this->pack()->nonPersist([
            $f1 = new Faction([
                'id' => 1,
                'name' => 'disabled',
                'enabled' => false
            ]),
            $f2 = new Faction([
                'id' => 2,
                'name' => 'enabled',
                'enabled' => true
            ])
        ]);

        $updated = clone $f1;
        $updated->name = 'new name';
        $this->assertEquals(0, $writer->update($updated));
        $this->assertEquals(0, $writer->delete($f1));
        $this->assertEquals($f1, Faction::repository()->withoutConstraints()->get(1));

        $f2->name = 'new name';
        $this->assertEquals(1, $writer->update($f2));
        $this->assertEquals(1, $writer->delete($f2));
        $this->assertNull(Faction::get(2));
    }
}
