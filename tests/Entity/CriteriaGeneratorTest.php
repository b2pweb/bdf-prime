<?php

namespace Entity;

use Bdf\Prime\Entity\CriteriaGenerator;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\TestEntity;
use PHPUnit\Framework\TestCase;

class CriteriaGeneratorTest extends TestCase
{
    use PrimeTestCase;

    protected function setUp(): void
    {
        $this->configurePrime();
    }

    protected function tearDown(): void
    {
        $this->unsetPrime();
    }

    public function test_generate_with_metadata()
    {
        $generator = new CriteriaGenerator('App\Entities\TestCriteria');
        $generator->parseMetadata($this->prime()->repository(TestEntity::class)->metadata());

        $this->assertEquals(<<<'PHP'
<?php

namespace App\Entities;

use Bdf\Prime\Entity\Criteria;

class TestCriteria extends Criteria
{
    public function id($value): self
    {
        $this->add('id', $value);
        return $this;
    }

    public function name($value): self
    {
        $this->add('name', $value);
        return $this;
    }

    public function dateInsert($value): self
    {
        $this->add('dateInsert', $value);
        return $this;
    }
}

PHP, $generator->dump());
    }

    public function test_generate_with_custom_filters()
    {
        $generator = new CriteriaGenerator('App\Entities\TestCriteria');
        $generator->parseCustomFilters($this->prime()->repository(TestEntity::class)->mapper()->filters());

        $this->assertEquals(<<<'PHP'
<?php

namespace App\Entities;

use Bdf\Prime\Entity\Criteria;

class TestCriteria extends Criteria
{
    public function idLike(string $id): self
    {
        $this->add('idLike', $id);
        return $this;
    }

    public function nameLike(string $search): self
    {
        $this->add('nameLike', $search);
        return $this;
    }

    public function join($value): self
    {
        $this->add('join', $value);
        return $this;
    }
}

PHP, $generator->dump());
    }

    public function test_loadFromFile()
    {
        $generator = new CriteriaGenerator('App\Entities\TestCriteria');
        $generator->loadFromFile(__DIR__.'/../_files/TestCriteria.php');

        $this->assertStringEqualsFile(__DIR__.'/../_files/TestCriteria.php', $generator->dump());
    }

    public function test_should_update_new_filters_when_loadFromFile_called()
    {
        $generator = new CriteriaGenerator('App\Entities\TestCriteria');

        $generator->loadFromFile(__DIR__.'/../_files/TestCriteria.php');
        $generator->parseMetadata($this->prime()->repository(TestEntity::class)->metadata());
        $generator->parseCustomFilters($this->prime()->repository(TestEntity::class)->mapper()->filters());

        $this->assertEquals(<<<'PHP'
<?php

namespace App\Entities;

use Bdf\Prime\Entity\Criteria;

class TestCriteria extends Criteria
{
    public function id($value): self
    {
        $this->add('id', $value);
        return $this;
    }

    public function name($value): self
    {
        $this->add('name', $value);
        return $this;
    }

    public function nameLike(string $search): self
    {
        $this->add('nameLike', $search);
        return $this;
    }

    /**
     * A custom criteria method
     *
     * @param string $foo
     * @return $this
     */
    public function myCustomFilter(string $foo): self
    {
        $this->add('id', $foo);
        $this->add('name', $foo);

        return $this;
    }

    public function dateInsert($value): self
    {
        $this->add('dateInsert', $value);
        return $this;
    }

    public function idLike(string $id): self
    {
        $this->add('idLike', $id);
        return $this;
    }

    public function join($value): self
    {
        $this->add('join', $value);
        return $this;
    }
}

PHP, $generator->dump());
    }
}
