<?php

namespace Console;

use Bdf\Prime\Console\CriteriaCommand;
use Bdf\Prime\PrimeTestCase;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

class CriteriaCommandTest extends TestCase
{
    use PrimeTestCase;

    private CriteriaCommand $command;
    private CommandTester $tester;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->primeStart();

        $this->command = new CriteriaCommand($this->prime());
        $this->tester = new CommandTester($this->command);

        $this->tmpDir = sys_get_temp_dir() . '/prime-criteria-' . md5(random_bytes(8));
    }

    protected function tearDown(): void
    {
        $this->unsetPrime();

        (new Filesystem())->remove($this->tmpDir);
    }

    public function test_execute_not_a_mapper()
    {
        $this->tester->execute(['mapper' => __DIR__ . '/UpgradeModels/Person.php', '--dry-run' => true, '--show' => true], [
            'verbosity' => OutputInterface::VERBOSITY_DEBUG,
        ]);

        $this->assertStringContainsString('\'Console\UpgradeModels\Person\' is not mapper class', $this->tester->getDisplay());
    }

    public function test_execute_single_mapper_show()
    {
        $this->tester->execute(['mapper' => __DIR__ . '/UpgradeModels/PersonMapper.php', '--dry-run' => true, '--show' => true], [
            'verbosity' => OutputInterface::VERBOSITY_DEBUG,
        ]);

        $this->assertStringContainsString(<<<'PHP'
<?php

namespace Console\UpgradeModels;

use Bdf\Prime\Entity\Criteria;

class PersonCriteria extends Criteria
{
    public function id($value): self
    {
        $this->add('id', $value);
        return $this;
    }

    public function firstName($value): self
    {
        $this->add('firstName', $value);
        return $this;
    }

    public function lastName($value): self
    {
        $this->add('lastName', $value);
        return $this;
    }
}

PHP, $this->tester->getDisplay());
    }

    public function test_execute_directory_show()
    {
        $this->tester->execute(['mapper' => __DIR__ . '/UpgradeModels', '--dry-run' => true, '--show' => true], [
            'verbosity' => OutputInterface::VERBOSITY_DEBUG,
        ]);

        $this->assertStringContainsString(<<<'PHP'
<?php

namespace Console\UpgradeModels;

use Bdf\Prime\Entity\Criteria;

class PersonCriteria extends Criteria
{
    public function id($value): self
    {
        $this->add('id', $value);
        return $this;
    }

    public function firstName($value): self
    {
        $this->add('firstName', $value);
        return $this;
    }

    public function lastName($value): self
    {
        $this->add('lastName', $value);
        return $this;
    }
}

PHP, $this->tester->getDisplay());

        $this->assertStringContainsString(<<<'PHP'
<?php

namespace Console\UpgradeModels;

use Bdf\Prime\Entity\Criteria;

class AddressCriteria extends Criteria
{
    public function id($value): self
    {
        $this->add('id', $value);
        return $this;
    }

    public function street($value): self
    {
        $this->add('street', $value);
        return $this;
    }

    public function number($value): self
    {
        $this->add('number', $value);
        return $this;
    }

    public function city($value): self
    {
        $this->add('city', $value);
        return $this;
    }

    public function zipCode($value): self
    {
        $this->add('zipCode', $value);
        return $this;
    }

    public function country($value): self
    {
        $this->add('country', $value);
        return $this;
    }
}

PHP, $this->tester->getDisplay());
    }

    public function test_execute_should_write_file_if_not_exists()
    {
        (new Filesystem())->copy(__DIR__.'/UpgradeModels/PersonMapper.php', $this->tmpDir.'/PersonMapper.php');

        $this->tester->execute(['mapper' => $this->tmpDir]);

        $this->assertFileExists($this->tmpDir . '/PersonCriteria.php');
        $this->assertSame(<<<'PHP'
<?php

namespace Console\UpgradeModels;

use Bdf\Prime\Entity\Criteria;

class PersonCriteria extends Criteria
{
    public function id($value): self
    {
        $this->add('id', $value);
        return $this;
    }

    public function firstName($value): self
    {
        $this->add('firstName', $value);
        return $this;
    }

    public function lastName($value): self
    {
        $this->add('lastName', $value);
        return $this;
    }
}

PHP, file_get_contents($this->tmpDir . '/PersonCriteria.php'));
    }

    public function test_execute_file_already_exists_should_ask_to_cancel()
    {
        (new Filesystem())->copy(__DIR__.'/UpgradeModels/PersonMapper.php', $this->tmpDir.'/PersonMapper.php');
        file_put_contents($this->tmpDir . '/PersonCriteria.php', $oldFile = <<<'PHP'
<?php

namespace Console\UpgradeModels;

use Bdf\Prime\Entity\Criteria;

class PersonCriteria extends Criteria
{
    public function id($value): self
    {
        $this->add('id', $value);
        return $this;
    }

    public function foo($value): self
    {
        $this->add('id', $value);
        return $this;
    }
}

PHP);

        $this->tester
            ->setInputs(['3']) // Cancel
            ->execute(['mapper' => $this->tmpDir])
        ;

        $this->assertStringContainsString('PersonCriteria.php\' exists. what do you want ?', $this->tester->getDisplay());
        $this->assertSame($oldFile, file_get_contents($this->tmpDir . '/PersonCriteria.php'));
    }

    public function test_execute_file_already_exists_should_ask_to_regenerated()
    {
        (new Filesystem())->copy(__DIR__.'/UpgradeModels/PersonMapper.php', $this->tmpDir.'/PersonMapper.php');
        file_put_contents($this->tmpDir . '/PersonCriteria.php', $oldFile = <<<'PHP'
<?php

namespace Console\UpgradeModels;

use Bdf\Prime\Entity\Criteria;

class PersonCriteria extends Criteria
{
    public function id($value): self
    {
        $this->add('id', $value);
        return $this;
    }

    public function foo($value): self
    {
        $this->add('id', $value);
        return $this;
    }
}

PHP);

        $this->tester
            ->setInputs(['1']) // Regenerate
            ->execute(['mapper' => $this->tmpDir])
        ;

        $this->assertStringContainsString('PersonCriteria.php\' exists. what do you want ?', $this->tester->getDisplay());
        $this->assertSame(<<<'PHP'
<?php

namespace Console\UpgradeModels;

use Bdf\Prime\Entity\Criteria;

class PersonCriteria extends Criteria
{
    public function id($value): self
    {
        $this->add('id', $value);
        return $this;
    }

    public function firstName($value): self
    {
        $this->add('firstName', $value);
        return $this;
    }

    public function lastName($value): self
    {
        $this->add('lastName', $value);
        return $this;
    }
}

PHP, file_get_contents($this->tmpDir . '/PersonCriteria.php'));
    }

    public function test_execute_file_already_exists_should_ask_to_update()
    {
        (new Filesystem())->copy(__DIR__.'/UpgradeModels/PersonMapper.php', $this->tmpDir.'/PersonMapper.php');
        file_put_contents($this->tmpDir . '/PersonCriteria.php', $oldFile = <<<'PHP'
<?php

namespace Console\UpgradeModels;

use Bdf\Prime\Entity\Criteria;

class PersonCriteria extends Criteria
{
    public function id($value): self
    {
        $this->add('id', $value);
        return $this;
    }

    public function foo($value): self
    {
        $this->add('id', $value);
        return $this;
    }
}

PHP);

        $this->tester
            ->setInputs(['2']) // Regenerate
            ->execute(['mapper' => $this->tmpDir])
        ;

        $this->assertStringContainsString('PersonCriteria.php\' exists. what do you want ?', $this->tester->getDisplay());
        $this->assertSame(<<<'PHP'
<?php

namespace Console\UpgradeModels;

use Bdf\Prime\Entity\Criteria;

class PersonCriteria extends Criteria
{
    public function id($value): self
    {
        $this->add('id', $value);
        return $this;
    }

    public function foo($value): self
    {
        $this->add('id', $value);
        return $this;
    }

    public function firstName($value): self
    {
        $this->add('firstName', $value);
        return $this;
    }

    public function lastName($value): self
    {
        $this->add('lastName', $value);
        return $this;
    }
}

PHP, file_get_contents($this->tmpDir . '/PersonCriteria.php'));
    }

    public function test_execute_file_already_exists_with_backup_option()
    {
        (new Filesystem())->copy(__DIR__.'/UpgradeModels/PersonMapper.php', $this->tmpDir.'/PersonMapper.php');
        file_put_contents($this->tmpDir . '/PersonCriteria.php', $oldFile = <<<'PHP'
<?php

namespace Console\UpgradeModels;

use Bdf\Prime\Entity\Criteria;

class PersonCriteria extends Criteria
{
    public function id($value): self
    {
        $this->add('id', $value);
        return $this;
    }

    public function foo($value): self
    {
        $this->add('id', $value);
        return $this;
    }
}

PHP);

        $this->tester
            ->setInputs(['2']) // Regenerate
            ->execute(['mapper' => $this->tmpDir, '--backup' => true])
        ;

        $this->assertSame(<<<'PHP'
<?php

namespace Console\UpgradeModels;

use Bdf\Prime\Entity\Criteria;

class PersonCriteria extends Criteria
{
    public function id($value): self
    {
        $this->add('id', $value);
        return $this;
    }

    public function foo($value): self
    {
        $this->add('id', $value);
        return $this;
    }

    public function firstName($value): self
    {
        $this->add('firstName', $value);
        return $this;
    }

    public function lastName($value): self
    {
        $this->add('lastName', $value);
        return $this;
    }
}

PHP, file_get_contents($this->tmpDir . '/PersonCriteria.php'));
        $this->assertSame($oldFile, file_get_contents($this->tmpDir . '/PersonCriteria.php.bak'));
    }
}
