<?php

namespace Bdf\Prime\Console;

use Bdf\Prime\Connection\ConnectionRegistry;
use Bdf\Prime\Customer;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class RunSqlCommandTest extends TestCase
{
    use PrimeTestCase;

    /** @var ConnectionRegistry */
    protected $manager;
    /** @var RunSqlCommand */
    protected $command;

    protected function setUp(): void
    {
        $this->primeStart();

        $this->manager = $this->prime()->connections();
        $this->command = new RunSqlCommand($this->manager);
    }

    /**
     *
     */
    protected function declareTestData($pack)
    {
        $pack->declareEntity([
            User::class,
        ]);
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->primeReset();
    }

    /**
     *
     */
    public function test_basic_sql()
    {
        $tester = new CommandTester($this->command);
        $tester->execute(['sql' => 'SELECT 1']);

        $this->assertStringContainsString('1', $tester->getDisplay());
    }

    /**
     *
     */
    public function test_sql()
    {
        $this->pack()->nonPersist($user = new User([
            'id'            => 1,
            'name'          => 'TEST1',
            'customer'      => new Customer(['id' => '1']),
            'dateInsert'    => new \DateTime(),
            'roles'         => ['2']
        ]));

        $tester = new CommandTester($this->command);
        $tester->execute(['sql' => 'SELECT * from user_']);

        $display = <<<EOF
array(1) {
  [0]=>
  array(5) {
    ["id_"]=>
    string(1) "1"
    ["name_"]=>
    string(5) "TEST1"
    ["roles_"]=>
    string(3) ",2,"
    ["customer_id"]=>
    string(1) "1"
    ["faction_id"]=>
    NULL
  }
}

EOF;

        $this->assertSame($display, $tester->getDisplay());
    }
}
