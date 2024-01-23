<?php

namespace Connection\Middleware\Explain\Report;

use Bdf\Prime\Connection\Middleware\Explain\Explainer;
use Bdf\Prime\Connection\Middleware\Explain\Platform\SqliteExplainPlatform;
use Bdf\Prime\Connection\Middleware\Explain\QueryType;
use Bdf\Prime\Connection\Middleware\Explain\Report\CsvExplainerReport;
use Bdf\Prime\Connection\Middleware\Explain\Report\ExplainResultFilter;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Like;
use Bdf\Prime\TestEmbeddedEntity;
use PHPUnit\Framework\TestCase;

class CsvExplainerReportTest extends TestCase
{
    use PrimeTestCase;

    private Explainer $explainer;
    private CsvExplainerReport $reporter;
    private string $file;

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

        $this->file = tempnam(sys_get_temp_dir(), 'csv_explain_report');
        $this->reporter = new CsvExplainerReport($this->file);
    }

    protected function tearDown(): void
    {
        unlink($this->file);
        $this->unsetPrime();
    }

    public function test_explain_single_report()
    {
        TestEmbeddedEntity::repository()->schema()->migrate();

        $query = TestEmbeddedEntity::where('id', [12, 45, 96])->order('name')->toRawSql();
        $result = $this->explainer->explain($query);
        $this->reporter->report($query, $result, __FILE__, 49);

        $this->assertEmpty(file_get_contents($this->file));

        $this->reporter->flush();

        $currenFile = __FILE__;

        $this->assertEquals(<<<CSV
"SELECT t0.* FROM foreign_ t0 WHERE t0.pk_id IN (12,45,96) ORDER BY t0.name_ ASC",$currenFile,49,primary,foreign_,"PRIMARY KEY",0,1,

CSV
, file_get_contents($this->file));
    }

    public function test_explain_multiple_report()
    {
        TestEmbeddedEntity::repository()->schema()->migrate();

        $query = TestEmbeddedEntity::where('id', [12, 45, 96])->order('name')->toRawSql();
        $result = $this->explainer->explain($query);
        $this->reporter->report($query, $result, __FILE__, 49);

        $query = TestEmbeddedEntity::where('name', (new Like('foo'))->startsWith())->order('name')->toRawSql();
        $result = $this->explainer->explain($query);
        $this->reporter->report($query, $result, __FILE__, 74);

        $this->reporter->flush();

        $currenFile = __FILE__;

        $this->assertEquals(<<<CSV
"SELECT t0.* FROM foreign_ t0 WHERE t0.pk_id IN (12,45,96) ORDER BY t0.name_ ASC",$currenFile,49,primary,foreign_,"PRIMARY KEY",0,1,
"SELECT t0.* FROM foreign_ t0 WHERE t0.name_ LIKE 'foo%' ORDER BY t0.name_ ASC",$currenFile,74,scan,foreign_,IDX_127C71CEC0EB25A3,0,0,

CSV
, file_get_contents($this->file));
    }

    public function test_explain_with_auto_flush()
    {
        $this->reporter = new CsvExplainerReport($this->file, true);
        TestEmbeddedEntity::repository()->schema()->migrate();

        $query = TestEmbeddedEntity::where('id', [12, 45, 96])->order('name')->toRawSql();
        $result = $this->explainer->explain($query);
        $this->reporter->report($query, $result, __FILE__, 49);

        $currenFile = __FILE__;

        $this->assertEquals(<<<CSV
"SELECT t0.* FROM foreign_ t0 WHERE t0.pk_id IN (12,45,96) ORDER BY t0.name_ ASC",$currenFile,49,primary,foreign_,"PRIMARY KEY",0,1,

CSV
, file_get_contents($this->file));
    }

    public function test_explain_filter()
    {
        $filter = new ExplainResultFilter();
        $filter->minimumType = QueryType::INDEX;
        $this->reporter = new CsvExplainerReport($this->file, true, $filter);

        TestEmbeddedEntity::repository()->schema()->migrate();

        $query = TestEmbeddedEntity::where('id', [12, 45, 96])->order('name')->toRawSql();
        $result = $this->explainer->explain($query);
        $this->reporter->report($query, $result, __FILE__, 49);

        $query = TestEmbeddedEntity::where('name', (new Like('foo'))->startsWith())->order('name')->toRawSql();
        $result = $this->explainer->explain($query);
        $this->reporter->report($query, $result, __FILE__, 74);

        $currenFile = __FILE__;

        $this->assertEquals(<<<CSV
"SELECT t0.* FROM foreign_ t0 WHERE t0.name_ LIKE 'foo%' ORDER BY t0.name_ ASC",$currenFile,74,scan,foreign_,IDX_127C71CEC0EB25A3,0,0,

CSV
            , file_get_contents($this->file));
    }

    public function test_flush_should_create_directory_if_not_exists()
    {
        $this->file = sys_get_temp_dir().'/csv_explain_report/'.uniqid().'-explain.csv';

        $this->reporter = new CsvExplainerReport($this->file);
        TestEmbeddedEntity::repository()->schema()->migrate();

        $query = TestEmbeddedEntity::where('id', [12, 45, 96])->order('name')->toRawSql();
        $result = $this->explainer->explain($query);
        $this->reporter->report($query, $result, __FILE__, 49);
        $this->reporter->flush();

        $this->assertDirectoryExists(dirname($this->file));

        $currenFile = __FILE__;

        $this->assertEquals(<<<CSV
"SELECT t0.* FROM foreign_ t0 WHERE t0.pk_id IN (12,45,96) ORDER BY t0.name_ ASC",$currenFile,49,primary,foreign_,"PRIMARY KEY",0,1,

CSV
            , file_get_contents($this->file));
    }

    public function test_flush_has_no_write_on_file_should_throw_error()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to open file /proc/cpuinfo');
        $this->expectExceptionMessage('Permission denied');

        $reporter = new CsvExplainerReport('/proc/cpuinfo');
        TestEmbeddedEntity::repository()->schema()->migrate();

        $query = TestEmbeddedEntity::where('id', [12, 45, 96])->order('name')->toRawSql();
        $result = $this->explainer->explain($query);
        $reporter->report($query, $result, __FILE__, 49);
        $reporter->flush();
    }
}
