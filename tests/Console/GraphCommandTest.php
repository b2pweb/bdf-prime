<?php

namespace Console;

require_once __DIR__.'/UpgradeModels/AddressMapper.php';
require_once __DIR__.'/UpgradeModels/Address.php';
require_once __DIR__.'/UpgradeModels/PersonMapper.php';
require_once __DIR__.'/UpgradeModels/Person.php';

use Bdf\Prime\Console\GraphCommand;
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

class GraphCommandTest extends TestCase
{
    use PrimeTestCase;

    private GraphCommand $command;

    protected function setUp(): void
    {
        $this->primeStart();

        $this->command = new GraphCommand($this->prime());
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
    public function test_execute()
    {
        $tester = new CommandTester($this->command);
        $tester->execute(['path' => __DIR__.'/UpgradeModels']);

        $lines = explode(PHP_EOL, $tester->getDisplay(true));
        $id = substr($lines[0], 9, 40);

        $this->assertEquals(<<<OUT
digraph "$id" {
graph [fontname="helvetica", fontsize=12];
node [fontname="helvetica", fontsize=12];
edge [fontname="helvetica", fontsize=12];
address [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">address</td></tr><tr><td border="0" align="left"><b>id</b></td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">street</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">number</td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">city</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">zipCode</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">country</td><td border="0" align="left"><font point-size="10">string</font></td></tr></table>> shape=plaintext ]
person [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">person</td></tr><tr><td border="0" align="left"><b>id</b></td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">firstName</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">lastName</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">address_id</td><td border="0" align="left"><font point-size="10">integer</font></td></tr></table>> shape=plaintext ]
}

OUT
 , implode(PHP_EOL, $lines)
);
    }

    /**
     *
     */
    public function test_execute_output_file()
    {
        $out = tempnam(sys_get_temp_dir(), 'graph');

        $tester = new CommandTester($this->command);
        $tester->execute([
            'path' => __DIR__.'/UpgradeModels',
            '--output' => $out
        ]);

        $lines = file($out);
        $id = substr($lines[0], 9, 40);

        $this->assertEquals(<<<OUT
digraph "$id" {
graph [fontname="helvetica", fontsize=12];
node [fontname="helvetica", fontsize=12];
edge [fontname="helvetica", fontsize=12];
address [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">address</td></tr><tr><td border="0" align="left"><b>id</b></td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">street</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">number</td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">city</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">zipCode</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">country</td><td border="0" align="left"><font point-size="10">string</font></td></tr></table>> shape=plaintext ]
person [label=<<table cellspacing="2" border="1" align="left" bgcolor="#eeeeec"><tr><td border="0" colspan="2" align="center" bgcolor="#fcaf3e">person</td></tr><tr><td border="0" align="left"><b>id</b></td><td border="0" align="left"><font point-size="10">integer</font></td></tr><tr><td border="0" align="left">firstName</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">lastName</td><td border="0" align="left"><font point-size="10">string</font></td></tr><tr><td border="0" align="left">address_id</td><td border="0" align="left"><font point-size="10">integer</font></td></tr></table>> shape=plaintext ]
}
OUT
 , implode($lines)
);
    }
}
