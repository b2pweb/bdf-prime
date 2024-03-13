<?php

namespace Connection\Middleware\Explain\Report;

use Bdf\Prime\Connection\Middleware\Explain\Explainer;
use Bdf\Prime\Connection\Middleware\Explain\Platform\SqliteExplainPlatform;
use Bdf\Prime\Connection\Middleware\Explain\QueryType;
use Bdf\Prime\Connection\Middleware\Explain\Report\ExplainResultFilter;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\TestEmbeddedEntity;
use PHPUnit\Framework\TestCase;

class ExplainResultFilterTest extends TestCase
{
    use PrimeTestCase;

    private Explainer $explainer;

    protected function setUp(): void
    {
        $this->configurePrime();

        $connection = $this->prime()->connection('test');
        $connection->connect();

        $r = new \ReflectionProperty($connection, '_conn');
        $r->setAccessible(true);

        $this->explainer = new Explainer(
            $r->getValue($connection),
            new SqliteExplainPlatform()
        );

        TestEmbeddedEntity::repository()->schema()->migrate();
    }

    protected function tearDown(): void
    {
        $this->unsetPrime();
    }

    public function test_minimumType()
    {
        TestEmbeddedEntity::repository()->schema()->migrate();

        $filter = new ExplainResultFilter();
        $filter->minimumType = QueryType::INDEX;

        $this->assertFalse($filter($this->explainer->explain('SELECT 1 + 1')));
        $this->assertFalse($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.pk_id = 12')));
        $this->assertFalse($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ = "foo"')));
        $this->assertTrue($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ LIKE "%foo%"')));

        $filter->minimumType = QueryType::PRIMARY;

        $this->assertFalse($filter($this->explainer->explain('SELECT 1 + 1')));
        $this->assertFalse($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.pk_id = 12')));
        $this->assertTrue($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ = "foo"')));
        $this->assertTrue($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ LIKE "%foo%"')));

        $filter->minimumType = null;

        $this->assertFalse($filter($this->explainer->explain('SELECT 1 + 1')));
        $this->assertFalse($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.pk_id = 12')));
        $this->assertFalse($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ = "foo"')));
        $this->assertFalse($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ LIKE "%foo%"')));
    }

    public function test_allScan()
    {
        TestEmbeddedEntity::repository()->schema()->migrate();

        $filter = new ExplainResultFilter();
        $filter->minimumType = QueryType::INDEX;
        $filter->minimumMatchingCriteria = 2;

        $filter->allScan = true;
        $this->assertFalse($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.pk_id = 12')));
        $this->assertFalse($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 JOIN foreign_ t1 ON t1.name_ = t0.name_ WHERE t0.city = "foo"')));
        $this->assertTrue($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ LIKE "%foo%"')));

        $filter->allScan = false;
        $filter->minimumMatchingCriteria = 1;

        $this->assertFalse($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.pk_id = 12')));
        $this->assertTrue($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 JOIN foreign_ t1 ON t1.name_ = t0.name_ WHERE t0.city = "foo"')));
        $this->assertTrue($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ LIKE "%foo%"')));
    }

    public function test_ignoreCovering()
    {
        TestEmbeddedEntity::repository()->schema()->migrate();

        $filter = new ExplainResultFilter();
        $filter->minimumType = QueryType::INDEX;

        $filter->ignoreCovering = true;
        $this->assertFalse($filter($this->explainer->explain('SELECT t0.name_ FROM foreign_ t0 WHERE t0.name_ LIKE "%foo%"')));
        $this->assertTrue($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ LIKE "%foo%"')));

        $filter->ignoreCovering = false;
        $this->assertTrue($filter($this->explainer->explain('SELECT t0.name_ FROM foreign_ t0 WHERE t0.name_ LIKE "%foo%"')));
        $this->assertTrue($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ LIKE "%foo%"')));
    }

    public function test_minimumRows()
    {
        TestEmbeddedEntity::repository()->schema()->migrate();

        $filter = new ExplainResultFilter();
        $filter->minimumRows = 50;

        $result = $this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ LIKE "%foo%"');
        $this->assertFalse($filter($result));

        $result->rows = 30;
        $this->assertFalse($filter($result));

        $result->rows = 50;
        $this->assertTrue($filter($result));
    }

    public function test_temporary()
    {
        TestEmbeddedEntity::repository()->schema()->migrate();

        $filter = new ExplainResultFilter();

        $filter->temporary = true;
        $this->assertFalse($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ LIKE "%foo%" ORDER BY t0.name_')));
        $this->assertTrue($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ LIKE "%foo%" ORDER BY t0.city')));

        $filter->temporary = false;
        $this->assertFalse($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ LIKE "%foo%" ORDER BY t0.name_')));
        $this->assertFalse($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ LIKE "%foo%" ORDER BY t0.city')));
    }

    public function test_minimumMatchingCriteria()
    {
        TestEmbeddedEntity::repository()->schema()->migrate();

        $filter = new ExplainResultFilter();
        $filter->minimumType = QueryType::INDEX;
        $filter->temporary = true;
        $filter->allScan = true;
        $filter->minimumRows = 50;

        $this->assertFalse($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ = "foo"')));
        $this->assertTrue($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 JOIN foreign_ t1 ON t1.pk_id = t0.pk_id WHERE t0.name_ LIKE "%foo%"'))); // type
        $this->assertTrue($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ LIKE "%foo%"'))); // type + allScan
        $this->assertTrue($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ LIKE "%foo%" ORDER BY t0.name_'))); // type
        $this->assertTrue($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ LIKE "%foo%" ORDER BY t0.city'))); // type + temporary
        $this->assertTrue($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 JOIN foreign_ t1 ON t1.city = t0.city WHERE t0.name_ LIKE "%foo%" ORDER BY t0.city'))); // type + temporary + allScan
        $result = $this->explainer->explain('SELECT t0.* FROM foreign_ t0 JOIN foreign_ t1 ON t1.city = t0.city WHERE t0.name_ LIKE "%foo%" ORDER BY t0.city');
        $result->rows = 50;
        $this->assertTrue($filter($result)); // type + temporary + allScan + minimumRows

        $filter->minimumMatchingCriteria = 2;
        $this->assertFalse($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ = "foo"')));
        $this->assertFalse($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 JOIN foreign_ t1 ON t1.pk_id = t0.pk_id WHERE t0.name_ LIKE "%foo%"'))); // type
        $this->assertTrue($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ LIKE "%foo%"'))); // type + allScan
        $this->assertTrue($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ LIKE "%foo%" ORDER BY t0.name_'))); // type + allScan
        $this->assertTrue($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ LIKE "%foo%" ORDER BY t0.city'))); // type + allScan + temporary
        $this->assertTrue($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 JOIN foreign_ t1 ON t1.city = t0.city WHERE t0.name_ LIKE "%foo%" ORDER BY t0.city'))); // type + temporary + allScan
        $result = $this->explainer->explain('SELECT t0.* FROM foreign_ t0 JOIN foreign_ t1 ON t1.city = t0.city WHERE t0.name_ LIKE "%foo%" ORDER BY t0.city');
        $result->rows = 50;
        $this->assertTrue($filter($result)); // type + temporary + allScan + minimumRows

        $filter->minimumMatchingCriteria = 3;
        $this->assertFalse($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ = "foo"')));
        $this->assertFalse($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 JOIN foreign_ t1 ON t1.pk_id = t0.pk_id WHERE t0.name_ LIKE "%foo%"'))); // type
        $this->assertFalse($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ LIKE "%foo%"'))); // type + allScan
        $this->assertFalse($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ LIKE "%foo%" ORDER BY t0.name_'))); // type + allScan
        $this->assertTrue($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ LIKE "%foo%" ORDER BY t0.city'))); // type + allScan + temporary
        $this->assertTrue($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 JOIN foreign_ t1 ON t1.city = t0.city WHERE t0.name_ LIKE "%foo%" ORDER BY t0.city'))); // type + temporary + allScan
        $result = $this->explainer->explain('SELECT t0.* FROM foreign_ t0 JOIN foreign_ t1 ON t1.city = t0.city WHERE t0.name_ LIKE "%foo%" ORDER BY t0.city');
        $result->rows = 50;
        $this->assertTrue($filter($result)); // type + temporary + allScan + minimumRows

        $filter->minimumMatchingCriteria = 4;
        $this->assertFalse($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ = "foo"')));
        $this->assertFalse($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 JOIN foreign_ t1 ON t1.pk_id = t0.pk_id WHERE t0.name_ LIKE "%foo%"'))); // type
        $this->assertFalse($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ LIKE "%foo%"'))); // type + allScan
        $this->assertFalse($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ LIKE "%foo%" ORDER BY t0.name_'))); // type + allScan
        $this->assertFalse($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 WHERE t0.name_ LIKE "%foo%" ORDER BY t0.city'))); // type + allScan + temporary
        $this->assertFalse($filter($this->explainer->explain('SELECT t0.* FROM foreign_ t0 JOIN foreign_ t1 ON t1.city = t0.city WHERE t0.name_ LIKE "%foo%" ORDER BY t0.city'))); // type + temporary + allScan
        $result = $this->explainer->explain('SELECT t0.* FROM foreign_ t0 JOIN foreign_ t1 ON t1.city = t0.city WHERE t0.name_ LIKE "%foo%" ORDER BY t0.city');
        $result->rows = 50;
        $this->assertTrue($filter($result)); // type + temporary + allScan + minimumRows
    }
}
