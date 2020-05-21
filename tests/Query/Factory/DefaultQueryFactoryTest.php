<?php

namespace Bdf\Prime\Query\Factory;

use Bdf\Prime\Connection\ConnectionInterface;
use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Compiler\CompilerInterface;
use Bdf\Prime\Query\Compiler\SqlCompiler;
use Bdf\Prime\Query\Custom\KeyValue\KeyValueQuery;
use Bdf\Prime\Query\Custom\KeyValue\KeyValueSqlCompiler;
use Bdf\Prime\Query\Query;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class DefaultQueryFactoryTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var DefaultQueryFactory
     */
    private $factory;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    protected function setUp(): void
    {
        $this->primeStart();

        $this->connection = Prime::connection('test');
        $this->factory = new DefaultQueryFactory($this->connection, new SqlCompiler($this->connection), [
            KeyValueQuery::class => KeyValueSqlCompiler::class
        ], [
            'keyValue' => KeyValueQuery::class
        ]);
    }

    /**
     *
     */
    public function test_make_with_class_name()
    {
        $query = $this->factory->make(KeyValueQuery::class);

        $this->assertInstanceOf(KeyValueQuery::class, $query);
        $this->assertSame($this->connection, $query->connection());
        $this->assertInstanceOf(KeyValueSqlCompiler::class, $query->compiler());
    }

    /**
     *
     */
    public function test_make_with_alias()
    {
        $query = $this->factory->make('keyValue');

        $this->assertInstanceOf(KeyValueQuery::class, $query);
        $this->assertSame($this->connection, $query->connection());
        $this->assertInstanceOf(KeyValueSqlCompiler::class, $query->compiler());
    }

    /**
     *
     */
    public function test_make_not_registered()
    {
        $query = $this->factory->make(Query::class);

        $this->assertInstanceOf(Query::class, $query);
        $this->assertSame($this->connection, $query->connection());
        $this->assertInstanceOf(SqlCompiler::class, $query->compiler());
    }

    /**
     *
     */
    public function test_compiler()
    {
        $this->assertInstanceOf(KeyValueSqlCompiler::class, $this->factory->compiler(KeyValueQuery::class));
        $this->assertSame($this->factory->compiler(KeyValueQuery::class), $this->factory->compiler(KeyValueQuery::class));

        $this->assertInstanceOf(SqlCompiler::class, $this->factory->compiler(Query::class));
        $this->assertSame($this->factory->compiler(Query::class), $this->factory->compiler(Query::class));
    }

    /**
     *
     */
    public function test_alias()
    {
        $this->factory->alias('custom_alias', Query::class);

        $this->assertInstanceOf(Query::class, $this->factory->make('custom_alias'));
    }

    /**
     *
     */
    public function test_register()
    {
        $compiler = $this->createMock(CompilerInterface::class);

        $this->factory->register(Query::class, $compiler);

        $this->assertSame($compiler, $this->factory->compiler(Query::class));
    }
}
