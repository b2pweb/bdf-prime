<?php

namespace Console;

require_once __DIR__.'/UpgradeModels/AddressMapper.php';
require_once __DIR__.'/UpgradeModels/Address.php';
require_once __DIR__.'/UpgradeModels/PersonMapper.php';
require_once __DIR__.'/UpgradeModels/Person.php';

use Bdf\Prime\Console\UpgraderCommand;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Schema\Builder\TypesHelperTableBuilder;
use Console\UpgradeModels\Address;
use Console\UpgradeModels\Person;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class UpgraderCommandTest extends TestCase
{
    use PrimeTestCase;

    /** @var UpgraderCommand */
    protected $command;

    protected function setUp(): void
    {
        $this->primeStart();

        $this->command = new UpgraderCommand($this->prime());
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
    public function test_simulate()
    {
        $tester = new CommandTester($this->command);
        $tester->execute(['path' => __DIR__.'/UpgradeModels']);

        $this->assertOutput(<<<OUT
Console\UpgradeModels\AddressMapper needs upgrade
CREATE TABLE address (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, street VARCHAR(255) NOT NULL, number INTEGER NOT NULL, city VARCHAR(255) NOT NULL, zipCode VARCHAR(255) NOT NULL, country VARCHAR(255) NOT NULL)

Console\UpgradeModels\PersonMapper needs upgrade
CREATE TABLE person (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, firstName VARCHAR(255) NOT NULL, lastName VARCHAR(255) NOT NULL, address_id INTEGER NOT NULL)

Found 2 upgrade(s)

OUT
 , $tester
);

        $this->assertFalse($this->prime()->connection()->schema()->has('person'));
        $this->assertFalse($this->prime()->connection()->schema()->has('address'));
    }

    /**
     *
     */
    public function test_execute()
    {
        $tester = new CommandTester($this->command);
        $tester->execute(['path' => __DIR__.'/UpgradeModels', '--execute' => true]);

        $this->assertOutput(<<<OUT
Console\UpgradeModels\AddressMapper needs upgrade
CREATE TABLE address (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, street VARCHAR(255) NOT NULL, number INTEGER NOT NULL, city VARCHAR(255) NOT NULL, zipCode VARCHAR(255) NOT NULL, country VARCHAR(255) NOT NULL)
launching query 
[OK]

Console\UpgradeModels\PersonMapper needs upgrade
CREATE TABLE person (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, firstName VARCHAR(255) NOT NULL, lastName VARCHAR(255) NOT NULL, address_id INTEGER NOT NULL)
launching query 
[OK]

Found 2 upgrade(s)

OUT
 , $tester
);

        $this->assertTrue($this->prime()->connection()->schema()->has('person'));
        $this->assertTrue($this->prime()->connection()->schema()->has('address'));
    }

    /**
     *
     */
    public function test_execute_already_migrated()
    {
        Person::repository()->schema()->migrate();
        Address::repository()->schema()->migrate();

        $tester = new CommandTester($this->command);
        $tester->execute(['path' => __DIR__.'/UpgradeModels', '--execute' => true]);

        $this->assertOutput(<<<OUT
Console\UpgradeModels\AddressMapper is up to date
Console\UpgradeModels\PersonMapper is up to date
Found 0 upgrade(s)

OUT
 , $tester
);
    }

    /**
     *
     */
    public function test_execute_with_and_without_useDrop()
    {
        $this->prime()->connection()->schema()->table('person', function (TypesHelperTableBuilder $builder) {
            $builder->integer('id')->autoincrement();
            $builder->string('foo');
            $builder->string('firstName');
            $builder->string('lastName');
        });

        Address::repository()->schema()->migrate();

        $tester = new CommandTester($this->command);
        $tester->execute(['path' => __DIR__.'/UpgradeModels']);

        $this->assertOutput(<<<OUT
Console\UpgradeModels\AddressMapper is up to date
Console\UpgradeModels\PersonMapper needs upgrade
ALTER TABLE person ADD COLUMN address_id INTEGER NOT NULL

Found 1 upgrade(s)

OUT
 , $tester
);


        $tester->execute(['path' => __DIR__.'/UpgradeModels', '--useDrop' => true]);

        $this->assertOutput(<<<OUT
Console\UpgradeModels\AddressMapper is up to date
Console\UpgradeModels\PersonMapper needs upgrade
CREATE TEMPORARY TABLE __temp__person AS SELECT id, firstName, lastName FROM person
DROP TABLE person
CREATE TABLE person (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, firstName VARCHAR(255) NOT NULL COLLATE BINARY, lastName VARCHAR(255) NOT NULL COLLATE BINARY, address_id INTEGER NOT NULL)
INSERT INTO person (id, firstName, lastName) SELECT id, firstName, lastName FROM __temp__person
DROP TABLE __temp__person

Found 5 upgrade(s)

OUT
            , $tester
        );
    }

    private function assertOutput(string $expected, CommandTester $tester): void
    {
        $this->assertEqualsCanonicalizing(explode(PHP_EOL, $expected), explode(PHP_EOL, $tester->getDisplay(true)));
    }
}
