<?php

namespace Console;

require_once __DIR__.'/UpgradeModels/AddressMapper.php';
require_once __DIR__.'/UpgradeModels/Address.php';
require_once __DIR__.'/UpgradeModels/PersonMapper.php';
require_once __DIR__.'/UpgradeModels/Person.php';

use Bdf\Prime\Console\UpgraderCommand;
use Bdf\Prime\Migration\MigrationManager;
use Bdf\Prime\Migration\Provider\FileMigrationProvider;
use Bdf\Prime\Migration\Provider\MigrationFactory;
use Bdf\Prime\Migration\Version\DbVersionRepository;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Schema\Builder\TypesHelperTableBuilder;
use Composer\InstalledVersions;
use Console\UpgradeModels\Address;
use Console\UpgradeModels\Person;
use PackageVersions\Installer;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class UpgraderCommandTest extends TestCase
{
    use PrimeTestCase;

    private const MIGRATION_PATH = __DIR__ . '/_tmp';

    private UpgraderCommand $command;
    private MigrationManager $migrationManager;

    protected function setUp(): void
    {
        $this->primeStart();

        $this->command = new UpgraderCommand($this->prime());
        $this->migrationManager = new MigrationManager(
            new DbVersionRepository($this->prime()->connection('test'), 'migration'),
            new FileMigrationProvider(new MigrationFactory($this->createMock(ContainerInterface::class)), self::MIGRATION_PATH)
        );

        mkdir(self::MIGRATION_PATH, 0777, true);
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->primeReset();
        (new Filesystem())->remove(self::MIGRATION_PATH);
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
    public function test_execute_migration_not_available()
    {
        Person::repository()->schema()->migrate();
        Address::repository()->schema()->migrate();

        $tester = new CommandTester($this->command);
        $tester->execute(['path' => __DIR__.'/UpgradeModels', '--migration' => 'foo']);

        $this->assertStringContainsString('[ERROR] Migration manager is not configured', $tester->getDisplay(true));
    }

    /**
     *
     */
    public function test_execute_migration_and_execute_options_are_not_compatible()
    {
        Person::repository()->schema()->migrate();
        Address::repository()->schema()->migrate();

        $this->command = new UpgraderCommand($this->prime(), $this->migrationManager);

        $tester = new CommandTester($this->command);
        $tester->execute(['path' => __DIR__.'/UpgradeModels', '--migration' => 'foo', '--execute' => true]);

        $this->assertStringContainsString('[ERROR] Cannot use --migration option with --execute option', $tester->getDisplay(true));
    }

    /**
     *
     */
    public function test_execute_migration_without_change_should_do_nothing()
    {
        Person::repository()->schema()->migrate();
        Address::repository()->schema()->migrate();

        $this->command = new UpgraderCommand($this->prime(), $this->migrationManager);

        $tester = new CommandTester($this->command);
        $tester->execute(['path' => __DIR__.'/UpgradeModels', '--migration' => 'foo']);

        $this->assertStringContainsString('[WARNING] Migration not created, no queries found', $tester->getDisplay(true));
        $this->assertEmpty(glob(self::MIGRATION_PATH.'/*.php'));
    }

    /**
     *
     */
    public function test_execute_migration_should_be_generated()
    {
        $this->command = new UpgraderCommand($this->prime(), $this->migrationManager);

        $tester = new CommandTester($this->command);
        $tester->execute(['path' => __DIR__.'/UpgradeModels', '--migration' => 'foo']);

        $this->assertStringContainsString('Found 2 upgrade(s)', $tester->getDisplay(true));
        $files = glob(self::MIGRATION_PATH.'/*.php');

        $this->assertNotEmpty($files);
        $this->assertStringEndsWith('Foo.php', $files[0]);

        $this->assertEquals(<<<'PHP'
<?php

use Bdf\Prime\Migration\Migration;

/**
 * Foo
 */
class Foo extends Migration
{
    /**
     * Initialize the migration
     */
    public function initialize(): void
    {
    }

    /**
     * Do the migration
     */
    public function up(): void
    {
        $this->update('CREATE TABLE address (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, street VARCHAR(255) NOT NULL, number INTEGER NOT NULL, city VARCHAR(255) NOT NULL, zipCode VARCHAR(255) NOT NULL, country VARCHAR(255) NOT NULL)', [], 'test');
        $this->update('CREATE TABLE person (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, firstName VARCHAR(255) NOT NULL, lastName VARCHAR(255) NOT NULL, address_id INTEGER NOT NULL)', [], 'test');
    }

    /**
     * Undo the migration
     */
    public function down(): void
    {
        $this->update('DROP TABLE address', [], 'test');
        $this->update('DROP TABLE person', [], 'test');
    }

    /**
     * {@inheritdoc}
     */
    public function stage(): string
    {
        return 'prepare';
    }
}

PHP, file_get_contents($files[0])
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

        if (version_compare(InstalledVersions::getVersion('doctrine/dbal'), '3.5.0', '>=')) {
            $this->assertOutput(<<<OUT
Console\UpgradeModels\AddressMapper is up to date
Console\UpgradeModels\PersonMapper needs upgrade
CREATE TEMPORARY TABLE __temp__person AS SELECT id, firstName, lastName FROM person
DROP TABLE person
CREATE TABLE person (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, firstName VARCHAR(255) NOT NULL COLLATE "BINARY", lastName VARCHAR(255) NOT NULL COLLATE "BINARY", address_id INTEGER NOT NULL)
INSERT INTO person (id, firstName, lastName) SELECT id, firstName, lastName FROM __temp__person
DROP TABLE __temp__person

Found 5 upgrade(s)

OUT
                , $tester
            );
        } else {
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
    }

    private function assertOutput(string $expected, CommandTester $tester): void
    {
        $this->assertEqualsCanonicalizing(explode(PHP_EOL, $expected), explode(PHP_EOL, $tester->getDisplay(true)));
    }
}
