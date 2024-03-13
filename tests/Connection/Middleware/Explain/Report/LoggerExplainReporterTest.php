<?php

namespace Connection\Middleware\Explain\Report;

use Bdf\Prime\Connection\Middleware\Explain\Explainer;
use Bdf\Prime\Connection\Middleware\Explain\Platform\SqliteExplainPlatform;
use Bdf\Prime\Connection\Middleware\Explain\Report\LoggerExplainReporter;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Like;
use Bdf\Prime\TestEmbeddedEntity;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LoggerExplainReporterTest extends TestCase
{
    use PrimeTestCase;

    private Explainer $explainer;
    private LoggerExplainReporter $reporter;
    private LoggerInterface $logger;

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

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->reporter = new LoggerExplainReporter($this->logger);
    }

    protected function tearDown(): void
    {
        $this->unsetPrime();
    }

    public function test_explain_order_using_b_tree()
    {
        TestEmbeddedEntity::repository()->schema()->migrate();

        $query = TestEmbeddedEntity::where('id', [12, 45, 96])->order('name')->toRawSql();
        $result = $this->explainer->explain($query);

        $this->logger->expects($this->once())->method('log')->with(
            'warning',
            'Explanation of query \'SELECT t0.* FROM foreign_ t0 WHERE t0.pk_id IN (12,45,96) ORDER BY t0.name_ ASC\' in '.__FILE__.':49: primary on foreign_ using index PRIMARY KEY on temporary table',
            [
                'query' => $query,
                'file' => __FILE__,
                'line' => 49,
                'explain' => $result,
            ]
        );

        $this->reporter->report($query, $result, __FILE__, 49);
    }

    public function test_explain_covering()
    {
        TestEmbeddedEntity::repository()->schema()->migrate();

        $query = TestEmbeddedEntity::select('name')->order('name')->toRawSql();
        $result = $this->explainer->explain($query);

        $this->logger->expects($this->once())->method('log')->with(
            'warning',
            'Explanation of query \'SELECT t0.name_ FROM foreign_ t0 ORDER BY t0.name_ ASC\' in '.__FILE__.':49: scan on foreign_ using covering index IDX_127C71CEC0EB25A3',
            [
                'query' => $query,
                'file' => __FILE__,
                'line' => 49,
                'explain' => $result,
            ]
        );

        $this->reporter->report($query, $result, __FILE__, 49);
    }

    public function test_explain_with_rows()
    {
        TestEmbeddedEntity::repository()->schema()->migrate();

        $query = TestEmbeddedEntity::where('name', (new Like('foo'))->contains())->toRawSql();
        $result = $this->explainer->explain($query);
        $result->rows = 150;

        $this->logger->expects($this->once())->method('log')->with(
            'error',
            'Explanation of query \'SELECT t0.* FROM foreign_ t0 WHERE t0.name_ LIKE \'%foo%\'\' in '.__FILE__.':49: scan on foreign_ (150 rows)',
            [
                'query' => $query,
                'file' => __FILE__,
                'line' => 49,
                'explain' => $result,
            ]
        );

        $this->reporter->report($query, $result, __FILE__, 49);
    }
}
