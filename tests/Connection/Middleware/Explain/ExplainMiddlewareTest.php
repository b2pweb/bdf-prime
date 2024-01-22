<?php

namespace Connection\Middleware\Explain;

use Bdf\Prime\Configuration;
use Bdf\Prime\Connection\Configuration\ConfigurationResolver;
use Bdf\Prime\Connection\ConnectionRegistry;
use Bdf\Prime\Connection\Factory\ChainFactory;
use Bdf\Prime\Connection\Factory\ConnectionFactory;
use Bdf\Prime\Connection\Middleware\Explain\ExplainMiddleware;
use Bdf\Prime\Connection\Middleware\Explain\ExplainResult;
use Bdf\Prime\Connection\Middleware\Explain\Report\ExplainReporter;
use Bdf\Prime\Connection\Middleware\Explain\Report\LoggerExplainReporter;
use Bdf\Prime\Connection\SimpleConnection;
use Bdf\Prime\ConnectionManager;
use Bdf\Prime\Locatorizable;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Like;
use Bdf\Prime\ServiceLocator;
use Bdf\Prime\TestEmbeddedEntity;
use Bdf\Prime\TestEntity;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ExplainMiddlewareTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    private $logger;
    private SimpleConnection $connection;

    protected function setUp(): void
    {
        $registry = new ConnectionRegistry(
            [
                'test' => [
                    'adapter' => 'sqlite',
                    'memory' => true
                ],
            ],
            new ChainFactory([new ConnectionFactory()]),
            new ConfigurationResolver([], $configuration = new Configuration())
        );

        $configuration
            ->setMiddlewares([
                new ExplainMiddleware(new ExplainReporter([new LoggerExplainReporter($this->logger = $this->createMock(LoggerInterface::class))]))
            ])
        ;

        $prime = new ServiceLocator(new ConnectionManager($registry));

        $this->connection = $prime->connection('test');

        Prime::configure($prime);
        Locatorizable::configure($prime);

        $this->pack()->declareEntity([TestEntity::class, TestEmbeddedEntity::class])->initialize();
    }

    protected function tearDown(): void
    {
        $this->pack()->destroy();
        $this->unsetPrime();
    }

    public function test_findById()
    {
        $this->logger->expects($this->once())->method('log')->with(
            'info',
            "Explanation of query 'SELECT * FROM test_ WHERE id = ? LIMIT 1' in ".__FILE__.":85: primary on test_ using index PRIMARY KEY",
            $this->callback(function ($context) {
                return $context['query'] === 'SELECT * FROM test_ WHERE id = ? LIMIT 1'
                    && $context['file'] === __FILE__
                    && $context['line'] === 85
                    && $context['explain'] instanceof ExplainResult
                ;
            })
        );

        TestEntity::findById(42);
    }

    public function test_complex_query()
    {
        $this->logger->expects($this->once())->method('log')->with(
            'warning',
            "Explanation of query 'SELECT t0.* FROM test_ t0 INNER JOIN foreign_ t1 ON t1.pk_id = t0.foreign_key WHERE t0.name LIKE ? OR t1.name_ LIKE ? LIMIT 5' in ".__FILE__.":105: scan on test_, foreign_ using index PRIMARY KEY",
            $this->callback(function ($context) {
                return $context['query'] === 'SELECT t0.* FROM test_ t0 INNER JOIN foreign_ t1 ON t1.pk_id = t0.foreign_key WHERE t0.name LIKE ? OR t1.name_ LIKE ? LIMIT 5'
                    && $context['file'] === __FILE__
                    && $context['line'] === 105
                    && $context['explain'] instanceof ExplainResult
                ;
            })
        );

        TestEntity::where('name', (new Like('foo%'))->startsWith())
            ->orWhere('foreign.name', (new Like('foo%'))->startsWith())
            ->limit(5)
            ->all()
        ;
    }

    public function test_update()
    {
        $this->logger->expects($this->once())->method('log')->with(
            'info',
            "Explanation of query 'UPDATE test_ SET name = ?, foreign_key = ?, date_insert = ? WHERE id = ?' in ".__FILE__.":132: primary on test_ using index PRIMARY KEY",
            $this->callback(function ($context) {
                return $context['query'] === 'UPDATE test_ SET name = ?, foreign_key = ?, date_insert = ? WHERE id = ?'
                    && $context['file'] === __FILE__
                    && $context['line'] === 132
                    && $context['explain'] instanceof ExplainResult
                ;
            })
        );

        $entity = new TestEntity([
            'id' => 42,
            'name' => 'foo',
            'foreign' => new TestEmbeddedEntity(['id' => 22])
        ]);

        $entity->insert();

        $entity->name = 'bar';
        $entity->update();
    }

    public function test_delete()
    {
        $this->logger->expects($this->once())->method('log')->with(
            'info',
            "Explanation of query 'DELETE FROM test_ WHERE id = ?' in ".__FILE__.":156: primary on test_ using index PRIMARY KEY",
            $this->callback(function ($context) {
                return $context['query'] === 'DELETE FROM test_ WHERE id = ?'
                    && $context['file'] === __FILE__
                    && $context['line'] === 156
                    && $context['explain'] instanceof ExplainResult
                ;
            })
        );

        $entity = new TestEntity([
            'id' => 42,
            'name' => 'foo',
            'foreign' => new TestEmbeddedEntity(['id' => 22])
        ]);

        $entity->insert();
        $entity->delete();
    }

    public function test_invalid_query()
    {
        $this->logger->expects($this->never())->method('log');

        try {
            TestEntity::whereRaw('invalid query')->all();
        } catch (\Exception $e) {
        }
    }
}
