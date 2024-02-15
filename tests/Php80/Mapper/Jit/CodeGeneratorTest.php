<?php

namespace Php80\Mapper\Jit;

use Bdf\Prime\Mapper\Jit\CodeGenerator;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Repository\EntityRepository;
use Php80\Mapper\_files\EntityWithJitMethod;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PHPUnit\Framework\TestCase;

class CodeGeneratorTest extends TestCase
{
    use PrimeTestCase;
    private CodeGenerator $generator;

    protected function setUp(): void
    {
        $this->primeStart();

        $this->generator = new CodeGenerator();
    }

    protected function tearDown(): void
    {
        $this->unsetPrime();
    }

    public function test_generateHook_simple()
    {
        $mapper = EntityWithJitMethod::repository()->mapper();

        $this->assertSame(<<<'PHP'
<?php

namespace Php80\Mapper\_files;

final class QuerySearch extends \Bdf\Prime\Mapper\Jit\JitQueryHook
{
    public function __invoke(\Bdf\Prime\Repository\EntityRepository $repository, string $name) : array
    {
        return $this->hook($repository->where('name', $name)->limit(15), \func_get_args())->all();
    }
    public function method() : string
    {
        return 'search';
    }
    public function class() : string
    {
        return \Php80\Mapper\_files\EntityWithJitMethodMapper::class;
    }
}
PHP
            , $this->generator->generateHook($mapper, 'search', 'QuerySearch')
        );
    }

    public function test_generateHook_first()
    {
        $code = $this->generator->generateHook(new QueriesBag(), 'withFirst', 'QueryWithFirst');

        $this->assertSame(<<<'PHP'
<?php

namespace Php80\Mapper\Jit;

final class QueryWithFirst extends \Bdf\Prime\Mapper\Jit\JitQueryHook
{
    public function __invoke(\Bdf\Prime\Repository\EntityRepository $repository, int $id)
    {
        return $this->hook($repository->where('id', '>', $id)->limit(1), \func_get_args())->first()->name();
    }
    public function method() : string
    {
        return 'withFirst';
    }
    public function class() : string
    {
        return \Php80\Mapper\Jit\QueriesBag::class;
    }
}
PHP
        , $code);
    }

    public function test_generateHook_execute()
    {
        $code = $this->generator->generateHook(new QueriesBag(), 'withExecute', 'QueryWithExecute');

        $this->assertSame(<<<'PHP'
<?php

namespace Php80\Mapper\Jit;

final class QueryWithExecute extends \Bdf\Prime\Mapper\Jit\JitQueryHook
{
    public function __invoke(\Bdf\Prime\Repository\EntityRepository $repository, int $id)
    {
        return $this->hook($repository->where('id', '>', $id), \func_get_args())->execute()->asAssociative();
    }
    public function method() : string
    {
        return 'withExecute';
    }
    public function class() : string
    {
        return \Php80\Mapper\Jit\QueriesBag::class;
    }
}
PHP
        , $code);
    }

    public function test_generateHook_execute_with_column_argument()
    {
        $code = $this->generator->generateHook(new QueriesBag(), 'executeWithColumns', 'QueryExecuteWithColumns');

        $this->assertSame(<<<'PHP'
<?php

namespace Php80\Mapper\Jit;

final class QueryExecuteWithColumns extends \Bdf\Prime\Mapper\Jit\JitQueryHook
{
    public function __invoke(\Bdf\Prime\Repository\EntityRepository $repository, int $id)
    {
        return $this->hook($repository->where('id', '>', $id)->select(['foo', 'bar']), \func_get_args())->execute(['foo', 'bar'])->asList();
    }
    public function method() : string
    {
        return 'executeWithColumns';
    }
    public function class() : string
    {
        return \Php80\Mapper\Jit\QueriesBag::class;
    }
}
PHP
        , $code);
    }

    public function test_generateHook_all_with_column_argument()
    {
        $code = $this->generator->generateHook(new QueriesBag(), 'allWithColumns', 'QueryAllWithColumns');

        $this->assertSame(<<<'PHP'
<?php

namespace Php80\Mapper\Jit;

final class QueryAllWithColumns extends \Bdf\Prime\Mapper\Jit\JitQueryHook
{
    public function __invoke(\Bdf\Prime\Repository\EntityRepository $repository, int $id)
    {
        return $this->hook($repository->where('id', '>', $id)->select(['foo', 'bar']), \func_get_args())->all(['foo', 'bar']);
    }
    public function method() : string
    {
        return 'allWithColumns';
    }
    public function class() : string
    {
        return \Php80\Mapper\Jit\QueriesBag::class;
    }
}
PHP
        , $code);
    }

    public function test_generateHook_first_with_column_argument()
    {
        $code = $this->generator->generateHook(new QueriesBag(), 'firstWithColumns', 'QueryFirstWithColumns');

        $this->assertSame(<<<'PHP'
<?php

namespace Php80\Mapper\Jit;

final class QueryFirstWithColumns extends \Bdf\Prime\Mapper\Jit\JitQueryHook
{
    public function __invoke(\Bdf\Prime\Repository\EntityRepository $repository, int $id)
    {
        return $this->hook($repository->where('id', '>', $id)->limit(1)->select(['foo', 'bar']), \func_get_args())->first(['foo', 'bar']);
    }
    public function method() : string
    {
        return 'firstWithColumns';
    }
    public function class() : string
    {
        return \Php80\Mapper\Jit\QueriesBag::class;
    }
}
PHP
        , $code);
    }

    public function test_generateHook_inRow()
    {
        $code = $this->generator->generateHook(new QueriesBag(), 'withInRow', 'QueryWithInRow');

        $this->assertSame(<<<'PHP'
<?php

namespace Php80\Mapper\Jit;

final class QueryWithInRow extends \Bdf\Prime\Mapper\Jit\JitQueryHook
{
    public function __invoke(\Bdf\Prime\Repository\EntityRepository $repository, int $id)
    {
        return $this->hook($repository->where('id', '>', $id)->limit(1)->select('foo'), \func_get_args())->inRow('foo');
    }
    public function method() : string
    {
        return 'withInRow';
    }
    public function class() : string
    {
        return \Php80\Mapper\Jit\QueriesBag::class;
    }
}
PHP
        , $code);
    }

    public function test_generateHook_inRows()
    {
        $code = $this->generator->generateHook(new QueriesBag(), 'withInRows', 'QueryWithInRows');

        $this->assertSame(<<<'PHP'
<?php

namespace Php80\Mapper\Jit;

final class QueryWithInRows extends \Bdf\Prime\Mapper\Jit\JitQueryHook
{
    public function __invoke(\Bdf\Prime\Repository\EntityRepository $repository, int $id)
    {
        return $this->hook($repository->where('id', '>', $id)->select('foo'), \func_get_args())->inRows('foo');
    }
    public function method() : string
    {
        return 'withInRows';
    }
    public function class() : string
    {
        return \Php80\Mapper\Jit\QueriesBag::class;
    }
}
PHP
        , $code);
    }

    public function test_generateHook_multipleStatements()
    {
        $code = $this->generator->generateHook(new QueriesBag(), 'multipleStatements', 'QueryMultipleStatements');

        $this->assertSame(<<<'PHP'
<?php

namespace Php80\Mapper\Jit;

final class QueryMultipleStatements extends \Bdf\Prime\Mapper\Jit\JitQueryHook
{
    public function __invoke(\Bdf\Prime\Repository\EntityRepository $repository, int $id)
    {
        $hash = 0;
        foreach ($this->hook($repository->where('id', $id)->select('value'), \func_get_args())->inRows('value') as $entity) {
            $hash ^= 17 * crc32($entity->value);
        }
        return $hash;
    }
    public function method() : string
    {
        return 'multipleStatements';
    }
    public function class() : string
    {
        return \Php80\Mapper\Jit\QueriesBag::class;
    }
}
PHP
        , $code);
    }

    public function test_generateHook_callingMapperMethod()
    {
        $code = $this->generator->generateHook(new QueriesBag(), 'callingMapperMethod', 'QueryMultipleStatements');

        $this->assertSame(<<<'PHP'
<?php

namespace Php80\Mapper\Jit;

final class QueryMultipleStatements extends \Bdf\Prime\Mapper\Jit\JitQueryHook
{
    public function __invoke(\Bdf\Prime\Repository\EntityRepository $repository)
    {
        return $this->hook($repository->where('hash', $this->mapper->configValue())->limit(1), \func_get_args())->first();
    }
    public function method() : string
    {
        return 'callingMapperMethod';
    }
    public function class() : string
    {
        return \Php80\Mapper\Jit\QueriesBag::class;
    }
}
PHP
        , $code);
    }

    public function test_generateHook_disallow_return_by_ref()
    {
        $this->assertNull($this->generator->generateHook(new QueriesBag(), 'returnByRef', 'QueryMultipleStatements'));
    }

    public function test_generateHook_disallow_undefined_method()
    {
        $this->assertNull($this->generator->generateHook(new QueriesBag(), 'undefined', 'QueryMultipleStatements'));
    }

    public function test_generateHook_disallow_class_without_code()
    {
        $this->assertNull($this->generator->generateHook(new \ArrayObject(), 'count', 'QueryMultipleStatements'));
    }

    public function test_generateInlinedQuery_simple()
    {
        $mapper = EntityWithJitMethod::repository()->mapper();

        $hookClassName = 'Q'.bin2hex(random_bytes(10));
        $code = $this->generator->generateHook($mapper, 'search', $hookClassName);
        eval(substr($code, 5)); // Remove "<?php" and execute the code

        $hookClassName = "\\Php80\\Mapper\\_files\\$hookClassName";
        /** @var \Bdf\Prime\Mapper\Jit\JitQueryHook $hook */
        $hook = new $hookClassName($mapper);
        $hook->query = 'SELECT t0.* FROM entity_with_jit_method t0 WHERE t0.name = ? LIMIT 15';
        $hook->argumentsMapping = [0 => 1];

        $this->assertEquals(<<<'PHP'
function (\Bdf\Prime\Repository\EntityRepository $repository, string $name) : array {
    return $repository->queries()->compiled('SELECT t0.* FROM entity_with_jit_method t0 WHERE t0.name = ? LIMIT 15')->withBindings([$name])->all();
}
PHP
        , $this->generator->generateInlinedQuery($hook));
    }

    public function test_generateInlinedQuery_multiple_statements()
    {
        $mapper = new QueriesBag();

        $hookClassName = 'Q'.bin2hex(random_bytes(10));
        $code = $this->generator->generateHook($mapper, 'multipleStatements', $hookClassName);
        eval(substr($code, 5)); // Remove "<?php" and execute the code

        $hookClassName = __NAMESPACE__."\\$hookClassName";
        /** @var \Bdf\Prime\Mapper\Jit\JitQueryHook $hook */
        $hook = new $hookClassName($mapper);
        $hook->query = 'SELECT t0.value FROM entity_with_jit_method t0';

        $this->assertEquals(<<<'PHP'
function (\Bdf\Prime\Repository\EntityRepository $repository, int $id) {
    $hash = 0;
    foreach ($repository->queries()->compiled('SELECT t0.value FROM entity_with_jit_method t0')->inRows('value') as $entity) {
        $hash ^= 17 * crc32($entity->value);
    }
    return $hash;
}
PHP
        , $this->generator->generateInlinedQuery($hook));
    }

    public function test_generateInlinedQuery_callingMapperMethod()
    {
        $mapper = new QueriesBag();

        $hookClassName = 'Q'.bin2hex(random_bytes(10));
        $code = $this->generator->generateHook($mapper, 'callingMapperMethod', $hookClassName);
        eval(substr($code, 5)); // Remove "<?php" and execute the code

        $hookClassName = __NAMESPACE__."\\$hookClassName";
        /** @var \Bdf\Prime\Mapper\Jit\JitQueryHook $hook */
        $hook = new $hookClassName($mapper);
        $hook->query = 'SELECT t0.value FROM entity_with_jit_method t0 WHERE hash = ?';

        $this->assertEquals(<<<'PHP'
function (\Bdf\Prime\Repository\EntityRepository $repository) {
    return $repository->queries()->compiled('SELECT t0.value FROM entity_with_jit_method t0 WHERE hash = ?')->first();
}
PHP
        , $this->generator->generateInlinedQuery($hook));
    }

    public function test_generateInlinedQuery_with_constants()
    {
        $mapper = EntityWithJitMethod::repository()->mapper();

        $hookClassName = 'Q'.bin2hex(random_bytes(10));
        $code = $this->generator->generateHook($mapper, 'search', $hookClassName);
        eval(substr($code, 5)); // Remove "<?php" and execute the code

        $hookClassName = "\\Php80\\Mapper\\_files\\$hookClassName";
        /** @var \Bdf\Prime\Mapper\Jit\JitQueryHook $hook */
        $hook = new $hookClassName($mapper);
        $hook->query = 'SELECT t0.value FROM entity_with_jit_method t0 WHERE name = ? LIMIT ?';
        $hook->argumentsMapping = [0 => 1];
        $hook->constants = [1 => 15];

        $this->assertEquals(<<<'PHP'
function (\Bdf\Prime\Repository\EntityRepository $repository, string $name) : array {
    return $repository->queries()->compiled('SELECT t0.value FROM entity_with_jit_method t0 WHERE name = ? LIMIT ?')->withBindings([$name, 15])->all();
}
PHP
        , $this->generator->generateInlinedQuery($hook));
    }

    public function test_generateInlinedQuery_invalid_class()
    {
        $hook = new class(EntityWithJitMethod::repository()->mapper()) extends \Bdf\Prime\Mapper\Jit\JitQueryHook {
            public function class(): string
            {
                return \ArrayObject::class;
            }

            public function method(): string
            {
                return 'count';
            }
        };

        $this->assertNull($this->generator->generateInlinedQuery($hook));
    }

    public function test_generateInlinedQuery_invalid_method()
    {
        $hook = new class(EntityWithJitMethod::repository()->mapper()) extends \Bdf\Prime\Mapper\Jit\JitQueryHook {
            public function class(): string
            {
                return QueriesBag::class;
            }

            public function method(): string
            {
                return 'undefined';
            }
        };

        $this->assertNull($this->generator->generateInlinedQuery($hook));
    }

    public function test_generateInlinedQuery_without_repository_parameter()
    {
        $hook = new class(EntityWithJitMethod::repository()->mapper()) extends \Bdf\Prime\Mapper\Jit\JitQueryHook {
            public function class(): string
            {
                return QueriesBag::class;
            }

            public function method(): string
            {
                return 'missingRepositoryParameter';
            }
        };

        $this->assertNull($this->generator->generateInlinedQuery($hook));

        $this->assertTrue($hook->invalid);
        $this->assertEquals('The method should have at least the repository as first parameter', $hook->reason);
    }

    public function test_generateInlinedQuery_associative_arguments()
    {
        $hook = new class(EntityWithJitMethod::repository()->mapper()) extends \Bdf\Prime\Mapper\Jit\JitQueryHook {
            public function class(): string
            {
                return QueriesBag::class;
            }

            public function method(): string
            {
                return 'withFirst';
            }
        };

        $hook->query = 'SELECT t0.value FROM entity_with_jit_method t0 WHERE name <> :name AND id = :id LIMIT :limit';
        $hook->argumentsMapping = ['id' => 1];
        $hook->constants = ['name' => 'system', 'limit' => 15];

        $this->assertEquals(<<<'PHP'
function (\Bdf\Prime\Repository\EntityRepository $repository, int $id) {
    return $repository->queries()->compiled('SELECT t0.value FROM entity_with_jit_method t0 WHERE name <> :name AND id = :id LIMIT :limit')->withBindings(['id' => $id, 'limit' => 15, 'name' => 'system'])->first()->name();
}
PHP
            , $this->generator->generateInlinedQuery($hook));
    }

    public function test_generateInlinedQuery_with_metadata()
    {
        $hook = new class(EntityWithJitMethod::repository()->mapper()) extends \Bdf\Prime\Mapper\Jit\JitQueryHook {
            public function class(): string
            {
                return QueriesBag::class;
            }

            public function method(): string
            {
                return 'withFirst';
            }
        };

        $hook->query = 'SELECT t0.value FROM entity_with_jit_method t0 WHERE id = ?';
        $hook->argumentsMapping = [0 => 1];
        $hook->metadataMapping = ['shard_value' => 1];
        $hook->metadataConstants = ['cache_key' => ['lifetime' => 3600]];

        $this->assertEquals(<<<'PHP'
function (\Bdf\Prime\Repository\EntityRepository $repository, int $id) {
    return $repository->queries()->compiled('SELECT t0.value FROM entity_with_jit_method t0 WHERE id = ?')->withBindings([$id])->withMetadata(['cache_key' => ['lifetime' => 3600], 'shard_value' => $id])->first()->name();
}
PHP
            , $this->generator->generateInlinedQuery($hook));
    }

    public function test_generateInlinedQuery_with_extension()
    {
        $hook = new class(EntityWithJitMethod::repository()->mapper()) extends \Bdf\Prime\Mapper\Jit\JitQueryHook {
            public function class(): string
            {
                return QueriesBag::class;
            }

            public function method(): string
            {
                return 'withFirst';
            }
        };

        $hook->query = 'SELECT t0.value FROM entity_with_jit_method t0 WHERE id = ?';
        $hook->argumentsMapping = [0 => 1];
        $hook->extensionParameters = ['byOptions' => ['attribute' => 'name', 'combine' => true]];

        $this->assertEquals(<<<'PHP'
function (\Bdf\Prime\Repository\EntityRepository $repository, int $id) {
    return $repository->queries()->compiled('SELECT t0.value FROM entity_with_jit_method t0 WHERE id = ?')->withBindings([$id])->withExtensionMetadata(['byOptions' => ['attribute' => 'name', 'combine' => true]])->first()->name();
}
PHP
            , $this->generator->generateInlinedQuery($hook));
    }

    public function test_castToExpr()
    {
        $this->assertEquals(new ConstFetch(new Name('null')), CodeGenerator::castToExpr(null));
        $this->assertEquals(new ConstFetch(new Name('true')), CodeGenerator::castToExpr(true));
        $this->assertEquals(new ConstFetch(new Name('false')), CodeGenerator::castToExpr(false));
        $this->assertEquals(new LNumber(123), CodeGenerator::castToExpr(123));
        $this->assertEquals(new DNumber(12.3), CodeGenerator::castToExpr(12.3));
        $this->assertEquals(new String_('foo bar'), CodeGenerator::castToExpr('foo bar'));
        $this->assertEquals(new Array_(), CodeGenerator::castToExpr([]));
        $this->assertEquals(new Array_([
            new ArrayItem(new LNumber(1)),
            new ArrayItem(new String_('foo')),
            new ArrayItem(new ConstFetch(new Name('true'))),
        ]), CodeGenerator::castToExpr([1, 'foo', true]));
        $this->assertEquals(new Array_([
            new ArrayItem(new LNumber(1), new String_('a')),
            new ArrayItem(new String_('foo'), new LNumber(5)),
            new ArrayItem(new ConstFetch(new Name('true')), new String_('ok')),
        ]), CodeGenerator::castToExpr(['a' => 1, 5 => 'foo', 'ok' => true]));
    }
}

class QueriesBag
{
    public function withFirst(EntityRepository $repository, int $id)
    {
        return $repository->where('id', '>', $id)->first()->name();
    }

    public function withExecute(EntityRepository $repository, int $id)
    {
        return $repository->where('id', '>', $id)->execute()->asAssociative();
    }

    public function executeWithColumns(EntityRepository $repository, int $id)
    {
        return $repository->where('id', '>', $id)->execute(['foo', 'bar'])->asList();
    }

    public function allWithColumns(EntityRepository $repository, int $id)
    {
        return $repository->where('id', '>', $id)->all(['foo', 'bar']);
    }

    public function firstWithColumns(EntityRepository $repository, int $id)
    {
        return $repository->where('id', '>', $id)->first(['foo', 'bar']);
    }

    public function withInRow(EntityRepository $repository, int $id)
    {
        return $repository->where('id', '>', $id)->inRow('foo');
    }

    public function withInRows(EntityRepository $repository, int $id)
    {
        return $repository->where('id', '>', $id)->inRows('foo');
    }

    public function multipleStatements(EntityRepository $repository, int $id)
    {
        $hash = 0;

        foreach ($repository->where('id', $id)->inRows('value') as $entity) {
            $hash ^= 17 * crc32($entity->value);
        }

        return $hash;
    }

    public function callingMapperMethod(EntityRepository $repository)
    {
        return $repository->where('hash', $this->configValue())->first();
    }

    private $prop1 = 'foo';
    private $prop2 = 'foo';

    public function &returnByRef(EntityRepository $repository, int $id)
    {
        if ($repository->where('id', $id)->first()) {
            return $this->prop1;
        }

        return $this->prop2;
    }

    public function missingRepositoryParameter(): ?string
    {
        return EntityWithJitMethod::where('id', 0)->inRow('name');
    }
}
