<?php

namespace Php80\Mapper\Jit;

use Bdf\Prime\Mapper\Jit\JitManager;
use Bdf\Prime\Mapper\Jit\MapperJit;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Query\Expression\Like;
use Bdf\Prime\Repository\EntityRepository;
use FilesystemIterator;
use Php80\Mapper\_files\EntityWithConstraint;
use Php80\Mapper\_files\EntityWithJitMethod;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use ReflectionMethod;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

use function is_dir;
use function rmdir;
use function unlink;

class MapperJitTest extends TestCase
{
    use PrimeTestCase;
    private MapperJit $jit;
    private JitManager $manager;
    private FilesystemAdapter $cache;

    protected function setUp(): void
    {
        $this->directory = '/tmp/' . uniqid('jit');
        $this->cache = new FilesystemAdapter('jit', 0, $this->directory);
        $this->manager = new JitManager(
            new Psr16Cache($this->cache),
            $this->directory,
            false
        );

        $this->configurePrime([
            'jit' => $this->manager,
        ]);

        $this->pack()->initialize();
        $this->jit = new MapperJit($this->manager, EntityWithJitMethod::repository()->mapper());
    }

    protected function tearDown(): void
    {
        unset($this->jit);
        unset($this->manager);

        if ($this->pack()->isInitialized()) {
            $this->pack()->destroy();
        }

        $this->unsetPrime();

        // Clean the directory recursively
        if (is_dir($this->directory)) {
            $it = new RecursiveDirectoryIterator($this->directory, FilesystemIterator::SKIP_DOTS);
            $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
        }
    }

    public function test_createHook()
    {
        $r = new ReflectionMethod($this->jit, 'createHook');
        $r->setAccessible(true);

        $hook = $r->invoke($this->jit, [new Foo(42), 'myQuery'], 'myQuery');
        $this->assertTrue(is_callable($hook));
        $this->assertSame(Foo::class, $hook->class());
        $this->assertSame('myQuery', $hook->method());
    }

    public function test_functional()
    {
        EntityWithJitMethod::repository()->schema()->migrate();
        $this->pack()->nonPersist([
            $foo = new EntityWithJitMethod(1, 'foo'),
            $bar = new EntityWithJitMethod(2, 'bar'),
            $baz = new EntityWithJitMethod(3, 'baz'),
            $bar2 = new EntityWithJitMethod(4, 'bar'),
        ]);

        for ($i = 0; $i < 10; ++$i) {
            // Ensure that the query is compiled
            EntityWithJitMethod::search('foo');
        }

        $this->assertEquals([$foo], EntityWithJitMethod::search('foo'));
        $this->assertEquals([$bar, $bar2], EntityWithJitMethod::search('bar'));
        $this->assertEquals([$baz], EntityWithJitMethod::search('baz'));
    }

    public function test_should_generate_code()
    {
        EntityWithJitMethod::repository()->schema()->migrate();

        $this->assertEmpty(glob($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/*'));

        EntityWithJitMethod::search('foo');

        $this->assertEquals(<<<'PHP'
<?php

namespace Php80\Mapper\_files;

final class EntityWithJitMethodMapperQuerySearch extends \Bdf\Prime\Mapper\Jit\JitQueryHook
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
        , file_get_contents($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/Php80_Mapper__files_EntityWithJitMethodMapperQuerySearch.php'));
        $this->assertFileDoesNotExist($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/compiled.php');

        EntityWithJitMethod::search('bar');
        $this->assertFileDoesNotExist($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/compiled.php');

        EntityWithJitMethod::search('baz');
        $this->assertFileExists($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/compiled.php');

        $this->assertEquals(<<<'PHP'
<?php

return [
'search' => function (\Bdf\Prime\Repository\EntityRepository $repository, string $name) : array {
    return $repository->queries()->compiled('SELECT t0.* FROM entity_with_jit_method t0 WHERE t0.name = ? LIMIT 15')->withBindings([$name])->all();
},

];
PHP
            , file_get_contents($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/compiled.php'));
    }

    public function test_should_not_generate_with_ambiguous_parameters()
    {
        EntityWithJitMethod::repository()->schema()->migrate();

        $this->assertEmpty(glob($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/*'));

        EntityWithJitMethod::ambiguous(42, 42);

        $this->assertEquals(<<<'PHP'
<?php

namespace Php80\Mapper\_files;

final class EntityWithJitMethodMapperQueryAmbiguous extends \Bdf\Prime\Mapper\Jit\JitQueryHook
{
    public function __invoke(\Bdf\Prime\Repository\EntityRepository $repository, int $id1, int $id2) : array
    {
        return $this->hook($repository->where('id', '>=', $id1)->where('id', '<=', $id2), \func_get_args())->all();
    }
    public function method() : string
    {
        return 'ambiguous';
    }
    public function class() : string
    {
        return \Php80\Mapper\_files\EntityWithJitMethodMapper::class;
    }
}
PHP
        , file_get_contents($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/Php80_Mapper__files_EntityWithJitMethodMapperQueryAmbiguous.php'));
        $this->assertFileDoesNotExist($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/compiled.php');

        // Will not generate the compiled file until the method is called with different parameters
        EntityWithJitMethod::ambiguous(42, 42);
        EntityWithJitMethod::ambiguous(42, 42);
        EntityWithJitMethod::ambiguous(42, 42);
        EntityWithJitMethod::ambiguous(42, 42);
        EntityWithJitMethod::ambiguous(42, 42);
        EntityWithJitMethod::ambiguous(42, 42);
        $this->assertFileDoesNotExist($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/compiled.php');

        EntityWithJitMethod::ambiguous(42, 66);

        $this->assertEquals(<<<'PHP'
<?php

return [
'ambiguous' => function (\Bdf\Prime\Repository\EntityRepository $repository, int $id1, int $id2) : array {
    return $repository->queries()->compiled('SELECT t0.* FROM entity_with_jit_method t0 WHERE t0.id >= ? AND t0.id <= ?')->withBindings([$id1, $id2])->all();
},

];
PHP
            , file_get_contents($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/compiled.php'));
    }

    public function test_with_constant()
    {
        EntityWithJitMethod::repository()->schema()->migrate();

        $this->assertEmpty(glob($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/*'));

        EntityWithJitMethod::withConstant(42);

        $this->assertEquals(<<<'PHP'
<?php

namespace Php80\Mapper\_files;

final class EntityWithJitMethodMapperQueryWithConstant extends \Bdf\Prime\Mapper\Jit\JitQueryHook
{
    public function __invoke(\Bdf\Prime\Repository\EntityRepository $repository, int $id) : array
    {
        return $this->hook($repository->where('name', 'foo')->where('id', '>=', $id)->limit(15)->select('id'), \func_get_args())->inRows('id');
    }
    public function method() : string
    {
        return 'withConstant';
    }
    public function class() : string
    {
        return \Php80\Mapper\_files\EntityWithJitMethodMapper::class;
    }
}
PHP
        , file_get_contents($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/Php80_Mapper__files_EntityWithJitMethodMapperQueryWithConstant.php'));
        $this->assertFileDoesNotExist($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/compiled.php');

        EntityWithJitMethod::withConstant(66);
        EntityWithJitMethod::withConstant(21);

        $this->assertEquals(<<<'PHP'
<?php

return [
'withConstant' => function (\Bdf\Prime\Repository\EntityRepository $repository, int $id) : array {
    return $repository->queries()->compiled('SELECT t0.id FROM entity_with_jit_method t0 WHERE t0.name = ? AND t0.id >= ? LIMIT 15')->withBindings(['foo', $id])->inRows('id');
},

];
PHP
            , file_get_contents($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/compiled.php'));
    }

    public function test_withMethodCall()
    {
        EntityWithJitMethod::repository()->schema()->migrate();

        $this->assertEmpty(glob($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/*'));

        EntityWithJitMethod::withMethodCall(42);

        $this->assertEquals(<<<'PHP'
<?php

namespace Php80\Mapper\_files;

final class EntityWithJitMethodMapperQueryWithMethodCall extends \Bdf\Prime\Mapper\Jit\JitQueryHook
{
    public function __invoke(\Bdf\Prime\Repository\EntityRepository $repository, string $name) : array
    {
        if (!$this->mapper->hasAccess()) {
            return [];
        }
        return $this->hook($repository->where('name', $name)->limit(15), \func_get_args())->all();
    }
    public function method() : string
    {
        return 'withMethodCall';
    }
    public function class() : string
    {
        return \Php80\Mapper\_files\EntityWithJitMethodMapper::class;
    }
}
PHP
        , file_get_contents($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/Php80_Mapper__files_EntityWithJitMethodMapperQueryWithMethodCall.php'));
        $this->assertFileDoesNotExist($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/compiled.php');

        EntityWithJitMethod::withMethodCall(66);
        EntityWithJitMethod::withMethodCall(21);
        EntityWithJitMethod::withMethodCall(21);

        $this->assertEquals(<<<'PHP'
<?php

return [
'withMethodCall' => function (\Bdf\Prime\Repository\EntityRepository $repository, string $name) : array {
    if (!$this->hasAccess()) {
        return [];
    }
    return $repository->queries()->compiled('SELECT t0.* FROM entity_with_jit_method t0 WHERE t0.name = ? LIMIT 15')->withBindings([$name])->all();
},

];
PHP
            , file_get_contents($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/compiled.php'));
    }

    public function test_should_not_compile_not_constant_query()
    {
        EntityWithJitMethod::repository()->schema()->migrate();

        $this->assertEmpty(glob($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/*'));

        EntityWithJitMethod::notConstantQuery('id', 42);

        $this->assertEquals(<<<'PHP'
<?php

namespace Php80\Mapper\_files;

final class EntityWithJitMethodMapperQueryNotConstantQuery extends \Bdf\Prime\Mapper\Jit\JitQueryHook
{
    public function __invoke(\Bdf\Prime\Repository\EntityRepository $repository, string $field, $value) : ?\Php80\Mapper\_files\EntityWithJitMethod
    {
        return $this->hook($repository->where($field, $value)->limit(1), \func_get_args())->first();
    }
    public function method() : string
    {
        return 'notConstantQuery';
    }
    public function class() : string
    {
        return \Php80\Mapper\_files\EntityWithJitMethodMapper::class;
    }
}
PHP
        , file_get_contents($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/Php80_Mapper__files_EntityWithJitMethodMapperQueryNotConstantQuery.php'));
        $this->assertFileDoesNotExist($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/compiled.php');

        EntityWithJitMethod::notConstantQuery('name', 'foo');
        EntityWithJitMethod::notConstantQuery('id', 21);
        EntityWithJitMethod::notConstantQuery('id', 21);
        EntityWithJitMethod::notConstantQuery('id', 21);
        EntityWithJitMethod::notConstantQuery('id', 21);
        $this->assertFileDoesNotExist($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/compiled.php');
    }

    public function test_with_keyvalue_query()
    {
        EntityWithJitMethod::repository()->schema()->migrate();

        $this->assertEmpty(glob($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/*'));

        EntityWithJitMethod::useKeyValueQuery('foo');

        $this->assertEquals(<<<'PHP'
<?php

namespace Php80\Mapper\_files;

final class EntityWithJitMethodMapperQueryUseKeyValueQuery extends \Bdf\Prime\Mapper\Jit\JitQueryHook
{
    public function __invoke(\Bdf\Prime\Repository\EntityRepository $repository, string $name) : ?\Php80\Mapper\_files\EntityWithJitMethod
    {
        return $this->hook($repository->keyValue()->where('name', $name)->limit(1), \func_get_args())->first();
    }
    public function method() : string
    {
        return 'useKeyValueQuery';
    }
    public function class() : string
    {
        return \Php80\Mapper\_files\EntityWithJitMethodMapper::class;
    }
}
PHP
            , file_get_contents($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/Php80_Mapper__files_EntityWithJitMethodMapperQueryUseKeyValueQuery.php'));
        $this->assertFileDoesNotExist($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/compiled.php');

        EntityWithJitMethod::useKeyValueQuery('bar');
        EntityWithJitMethod::useKeyValueQuery('baz');

        $this->assertEquals(<<<'PHP'
<?php

return [
'useKeyValueQuery' => function (\Bdf\Prime\Repository\EntityRepository $repository, string $name) : ?\Php80\Mapper\_files\EntityWithJitMethod {
    return $repository->queries()->compiled('SELECT * FROM entity_with_jit_method WHERE name = ? LIMIT 1')->withBindings([$name])->first();
},

];
PHP
            , file_get_contents($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/compiled.php'));
    }

    public function test_notSelectQuery()
    {
        EntityWithJitMethod::repository()->schema()->migrate();

        $this->assertEmpty(glob($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/*'));

        EntityWithJitMethod::notSelectQuery('foo');

        $this->assertEquals(<<<'PHP'
<?php

namespace Php80\Mapper\_files;

final class EntityWithJitMethodMapperQueryNotSelectQuery extends \Bdf\Prime\Mapper\Jit\JitQueryHook
{
    public function __invoke(\Bdf\Prime\Repository\EntityRepository $repository)
    {
        $this->hook($repository->queries()->make(\Bdf\Prime\Query\Custom\BulkInsert\BulkInsertQuery::class)->values(['name' => 'foo']), \func_get_args())->execute();
    }
    public function method() : string
    {
        return 'notSelectQuery';
    }
    public function class() : string
    {
        return \Php80\Mapper\_files\EntityWithJitMethodMapper::class;
    }
}
PHP
            , file_get_contents($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/Php80_Mapper__files_EntityWithJitMethodMapperQueryNotSelectQuery.php'));
        $this->assertFileDoesNotExist($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/compiled.php');

        EntityWithJitMethod::notSelectQuery();
        EntityWithJitMethod::notSelectQuery();
        $this->assertFileDoesNotExist($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/compiled.php');
    }

    public function test_changingArgumentMapping()
    {
        EntityWithJitMethod::repository()->schema()->migrate();

        $this->assertEmpty(glob($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/*'));

        EntityWithJitMethod::changingArgumentMapping(12, 14);

        $this->assertEquals(<<<'PHP'
<?php

namespace Php80\Mapper\_files;

final class EntityWithJitMethodMapperQueryChangingArgumentMapping extends \Bdf\Prime\Mapper\Jit\JitQueryHook
{
    public function __invoke(\Bdf\Prime\Repository\EntityRepository $repository, int $id1, int $id2) : array
    {
        return $this->hook($repository->where('id', '>=', min($id1, $id2))->where('id', '<=', max($id2, $id2)), \func_get_args())->all();
    }
    public function method() : string
    {
        return 'changingArgumentMapping';
    }
    public function class() : string
    {
        return \Php80\Mapper\_files\EntityWithJitMethodMapper::class;
    }
}
PHP
            , file_get_contents($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/Php80_Mapper__files_EntityWithJitMethodMapperQueryChangingArgumentMapping.php'));
        $this->assertFileDoesNotExist($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/compiled.php');

        EntityWithJitMethod::changingArgumentMapping(66, 15);
        EntityWithJitMethod::changingArgumentMapping(14, 23);
        $this->assertFileDoesNotExist($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/compiled.php');
    }

    public function test_changing_constant()
    {
        EntityWithJitMethod::repository()->schema()->migrate();

        $this->assertEmpty(glob($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/*'));

        EntityWithJitMethod::constantNotSoConstant();

        $this->assertEquals(<<<'PHP'
<?php

namespace Php80\Mapper\_files;

final class EntityWithJitMethodMapperQueryConstantNotSoConstant extends \Bdf\Prime\Mapper\Jit\JitQueryHook
{
    public function __invoke(\Bdf\Prime\Repository\EntityRepository $repository) : array
    {
        return $this->hook($repository->where('id', rand(0, 100)), \func_get_args())->all();
    }
    public function method() : string
    {
        return 'constantNotSoConstant';
    }
    public function class() : string
    {
        return \Php80\Mapper\_files\EntityWithJitMethodMapper::class;
    }
}
PHP
            , file_get_contents($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/Php80_Mapper__files_EntityWithJitMethodMapperQueryConstantNotSoConstant.php'));
        $this->assertFileDoesNotExist($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/compiled.php');

        EntityWithJitMethod::constantNotSoConstant();
        EntityWithJitMethod::constantNotSoConstant();
        $this->assertFileDoesNotExist($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/compiled.php');
    }

    public function test_missingRepositoryParameter()
    {
        EntityWithJitMethod::repository()->schema()->migrate();

        $this->assertEmpty(glob($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/*'));

        EntityWithJitMethod::missingRepositoryParameter();

        $this->assertEquals(<<<'PHP'
<?php

namespace Php80\Mapper\_files;

final class EntityWithJitMethodMapperQueryMissingRepositoryParameter extends \Bdf\Prime\Mapper\Jit\JitQueryHook
{
    public function __invoke()
    {
        return 'ok';
    }
    public function method() : string
    {
        return 'missingRepositoryParameter';
    }
    public function class() : string
    {
        return \Php80\Mapper\_files\EntityWithJitMethodMapper::class;
    }
}
PHP
            , file_get_contents($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/Php80_Mapper__files_EntityWithJitMethodMapperQueryMissingRepositoryParameter.php'));
        $this->assertFileDoesNotExist($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/compiled.php');

        EntityWithJitMethod::missingRepositoryParameter();
        EntityWithJitMethod::missingRepositoryParameter();
        $this->assertFileDoesNotExist($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/compiled.php');
    }

    public function test_with_constraint()
    {
        EntityWithConstraint::repository()->schema()->migrate();

        $this->assertEmpty(glob($this->directory . '/Php80_Mapper__files_EntityWithConstraintMapper/*'));

        EntityWithConstraint::search('foo');

        $this->assertEquals(<<<'PHP'
<?php

namespace Php80\Mapper\_files;

final class EntityWithConstraintMapperQuerySearch extends \Bdf\Prime\Mapper\Jit\JitQueryHook
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
        return \Php80\Mapper\_files\EntityWithConstraintMapper::class;
    }
}
PHP
            , file_get_contents($this->directory . '/Php80_Mapper__files_EntityWithConstraintMapper/Php80_Mapper__files_EntityWithConstraintMapperQuerySearch.php'));
        $this->assertFileDoesNotExist($this->directory . '/Php80_Mapper__files_EntityWithConstraintMapper/compiled.php');

        EntityWithConstraint::search('bar');
        EntityWithConstraint::search('baz');

        $this->assertSame(<<<'PHP'
<?php

return [
'search' => function (\Bdf\Prime\Repository\EntityRepository $repository, string $name) : array {
    return $repository->queries()->compiled('SELECT t0.* FROM entity_with_constraint t0 WHERE t0.name = ? AND (t0.active = ? AND t0.id > ?) LIMIT 15')->withBindings([$name, 1, 5])->all();
},

];
PHP
        , file_get_contents($this->directory . '/Php80_Mapper__files_EntityWithConstraintMapper/compiled.php'));
    }

    public function test_with_metadata()
    {
        EntityWithJitMethod::repository()->schema()->migrate();

        $this->assertEmpty(glob($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/*'));

        EntityWithJitMethod::withCustomMetadata('foo');

        $this->assertEquals(<<<'PHP'
<?php

namespace Php80\Mapper\_files;

final class EntityWithJitMethodMapperQueryWithCustomMetadata extends \Bdf\Prime\Mapper\Jit\JitQueryHook
{
    public function __invoke(\Bdf\Prime\Repository\EntityRepository $repository, string $name) : \Bdf\Prime\Collection\EntityCollection
    {
        return $this->hook($repository->where('name', $name)->wrapAs(\Bdf\Prime\Collection\EntityCollection::class)->useCache(3600), \func_get_args())->all();
    }
    public function method() : string
    {
        return 'withCustomMetadata';
    }
    public function class() : string
    {
        return \Php80\Mapper\_files\EntityWithJitMethodMapper::class;
    }
}
PHP
            , file_get_contents($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/Php80_Mapper__files_EntityWithJitMethodMapperQueryWithCustomMetadata.php'));
        $this->assertFileDoesNotExist($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/compiled.php');

        EntityWithJitMethod::withCustomMetadata('bar');
        EntityWithJitMethod::withCustomMetadata('baz');

        $this->assertSame(<<<'PHP'
<?php

return [
'withCustomMetadata' => function (\Bdf\Prime\Repository\EntityRepository $repository, string $name) : \Bdf\Prime\Collection\EntityCollection {
    return $repository->queries()->compiled('SELECT t0.* FROM entity_with_jit_method t0 WHERE t0.name = ?')->withBindings([$name])->withMetadata(['cache_key' => ['key' => null, 'namespace' => null, 'lifetime' => 3600], 'wrapper' => 'Bdf\\Prime\\Collection\\EntityCollection'])->all();
},

];
PHP
        , file_get_contents($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/compiled.php'));
    }

    public function test_with_extension()
    {
        EntityWithJitMethod::repository()->schema()->migrate();

        $this->assertEmpty(glob($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/*'));

        EntityWithJitMethod::withExtensionCalls(10, 12);

        $this->assertEquals(<<<'PHP'
<?php

namespace Php80\Mapper\_files;

final class EntityWithJitMethodMapperQueryWithExtensionCalls extends \Bdf\Prime\Mapper\Jit\JitQueryHook
{
    public function __invoke(\Bdf\Prime\Repository\EntityRepository $repository, int $id1, int $id2) : array
    {
        return $this->hook($repository->where('id', '>=', $id1)->where('id', '<=', $id2)->by('name', true)->with('rel'), \func_get_args())->all();
    }
    public function method() : string
    {
        return 'withExtensionCalls';
    }
    public function class() : string
    {
        return \Php80\Mapper\_files\EntityWithJitMethodMapper::class;
    }
}
PHP
            , file_get_contents($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/Php80_Mapper__files_EntityWithJitMethodMapperQueryWithExtensionCalls.php'));
        $this->assertFileDoesNotExist($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/compiled.php');

        EntityWithJitMethod::withExtensionCalls(45, 65);
        EntityWithJitMethod::withExtensionCalls(5, 111);

        $this->assertSame(<<<'PHP'
<?php

return [
'withExtensionCalls' => function (\Bdf\Prime\Repository\EntityRepository $repository, int $id1, int $id2) : array {
    return $repository->queries()->compiled('SELECT t0.* FROM entity_with_jit_method t0 WHERE t0.id >= ? AND t0.id <= ?')->withBindings([$id1, $id2])->withExtensionMetadata(['byOptions' => ['attribute' => 'name', 'combine' => true], 'withRelations' => ['rel' => ['constraints' => [], 'relations' => []]]])->all();
},

];
PHP
        , file_get_contents($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/compiled.php'));
    }

    public function test_disable_constraints_should_ignore_jit()
    {
        EntityWithConstraint::repository()->schema()->migrate();

        $this->assertEmpty(glob($this->directory . '/Php80_Mapper__files_EntityWithConstraintMapper/*'));

        EntityWithConstraint::repository()->withoutConstraints()->search('foo');
        $this->assertEmpty(glob($this->directory . '/Php80_Mapper__files_EntityWithConstraintMapper/*'));
        $this->assertFileDoesNotExist($this->directory . '/Php80_Mapper__files_EntityWithConstraintMapper/compiled.php');

        EntityWithConstraint::repository()->withoutConstraints()->search('foo');
        EntityWithConstraint::repository()->withoutConstraints()->search('foo');
        $this->assertFileDoesNotExist($this->directory . '/Php80_Mapper__files_EntityWithConstraintMapper/compiled.php');

        EntityWithConstraint::repository()->search('foo');
        EntityWithConstraint::repository()->search('foo');
        EntityWithConstraint::repository()->search('foo');

        $this->assertSame(<<<'PHP'
<?php

return [
'search' => function (\Bdf\Prime\Repository\EntityRepository $repository, string $name) : array {
    return $repository->queries()->compiled('SELECT t0.* FROM entity_with_constraint t0 WHERE t0.name = ? AND (t0.active = ? AND t0.id > ?) LIMIT 15')->withBindings([$name, 1, 5])->all();
},

];
PHP
            , file_get_contents($this->directory . '/Php80_Mapper__files_EntityWithConstraintMapper/compiled.php'));

        $entity1 = new EntityWithConstraint(1, 'foo', true);
        $entity2 = new EntityWithConstraint(10, 'bar', false);
        $entity3 = new EntityWithConstraint(12, 'baz', true);

        $entity1->insert();
        $entity2->insert();
        $entity3->insert();

        $this->assertEquals([], EntityWithConstraint::repository()->search('foo'));
        $this->assertEquals([$entity1], EntityWithConstraint::repository()->withoutConstraints()->search('foo'));
        $this->assertEquals([], EntityWithConstraint::repository()->search('bar'));
        $this->assertEquals([$entity2], EntityWithConstraint::repository()->withoutConstraints()->search('bar'));
        $this->assertEquals([$entity3], EntityWithConstraint::repository()->search('baz'));
        $this->assertEquals([$entity3], EntityWithConstraint::repository()->withoutConstraints()->search('baz'));
    }

    public function test_generate_multiple_queries()
    {
        EntityWithJitMethod::repository()->schema()->migrate();

        EntityWithJitMethod::useKeyValueQuery('foo');
        EntityWithJitMethod::useKeyValueQuery('foo');
        EntityWithJitMethod::useKeyValueQuery('foo');
        EntityWithJitMethod::search('foo');
        EntityWithJitMethod::search('foo');
        EntityWithJitMethod::search('foo');
        EntityWithJitMethod::withConstant(42);
        EntityWithJitMethod::withConstant(42);
        EntityWithJitMethod::withConstant(42);

        $this->assertEqualsCanonicalizing([
            '.', '..',
            'Php80_Mapper__files_EntityWithJitMethodMapperQuerySearch.php',
            'Php80_Mapper__files_EntityWithJitMethodMapperQueryUseKeyValueQuery.php',
            'Php80_Mapper__files_EntityWithJitMethodMapperQueryWithConstant.php',
            'compiled.php',
        ], scandir($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper'));

        $this->assertEquals(<<<'PHP'
<?php

return [
'useKeyValueQuery' => function (\Bdf\Prime\Repository\EntityRepository $repository, string $name) : ?\Php80\Mapper\_files\EntityWithJitMethod {
    return $repository->queries()->compiled('SELECT * FROM entity_with_jit_method WHERE name = ? LIMIT 1')->withBindings([$name])->first();
},

'search' => function (\Bdf\Prime\Repository\EntityRepository $repository, string $name) : array {
    return $repository->queries()->compiled('SELECT t0.* FROM entity_with_jit_method t0 WHERE t0.name = ? LIMIT 15')->withBindings([$name])->all();
},

'withConstant' => function (\Bdf\Prime\Repository\EntityRepository $repository, int $id) : array {
    return $repository->queries()->compiled('SELECT t0.id FROM entity_with_jit_method t0 WHERE t0.name = ? AND t0.id >= ? LIMIT 15')->withBindings(['foo', $id])->inRows('id');
},

];
PHP
            , file_get_contents($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper/compiled.php'));
    }

    public function test_should_save_hook_metadata_on_destruct()
    {
        EntityWithJitMethod::repository()->schema()->migrate();

        EntityWithJitMethod::useKeyValueQuery('foo');
        EntityWithJitMethod::search('foo');
        EntityWithJitMethod::search('foo');
        EntityWithJitMethod::withConstant(42);

        EntityWithJitMethod::mapper()->destroy();

        $this->pack()->destroy();
        $this->unsetPrime();
        gc_collect_cycles();

        $metadata = $this->cache->getItem('jit.hooks.Php80_Mapper__files_EntityWithJitMethodMapper')->get();

        $this->assertEquals([
            'useKeyValueQuery' => [
                'hookClass' => 'Php80\Mapper\_files\EntityWithJitMethodMapperQueryUseKeyValueQuery',
                'hookFile' => $metadata['useKeyValueQuery']['hookFile'],
                'count' => 1,
                'query' => 'SELECT * FROM entity_with_jit_method WHERE name = ? LIMIT 1',
                'argumentsMapping' => [0 => 1],
                'constants' => [],
                'invalid' => false,
                'reason' => null,
            ],
            'search' => [
                'hookClass' => 'Php80\Mapper\_files\EntityWithJitMethodMapperQuerySearch',
                'hookFile' => $metadata['search']['hookFile'],
                'count' => 2,
                'query' => 'SELECT t0.* FROM entity_with_jit_method t0 WHERE t0.name = ? LIMIT 15',
                'argumentsMapping' => [0 => 1],
                'constants' => [],
                'invalid' => false,
                'reason' => null,
            ],
            'withConstant' => [
                'hookClass' => 'Php80\Mapper\_files\EntityWithJitMethodMapperQueryWithConstant',
                'hookFile' => $metadata['withConstant']['hookFile'],
                'count' => 1,
                'query' => 'SELECT t0.id FROM entity_with_jit_method t0 WHERE t0.name = ? AND t0.id >= ? LIMIT 15',
                'argumentsMapping' => [1 => 1],
                'constants' => [0 => 'foo'],
                'invalid' => false,
                'reason' => null,
            ],
        ], $metadata);

        $this->assertEqualsCanonicalizing([
            '.', '..',
            'Php80_Mapper__files_EntityWithJitMethodMapperQuerySearch.php',
            'Php80_Mapper__files_EntityWithJitMethodMapperQueryUseKeyValueQuery.php',
            'Php80_Mapper__files_EntityWithJitMethodMapperQueryWithConstant.php',
        ], scandir($this->directory . '/Php80_Mapper__files_EntityWithJitMethodMapper'));
    }

    public function test_load_hooks_from_cache()
    {
        EntityWithJitMethod::repository()->schema()->migrate();

        EntityWithJitMethod::useKeyValueQuery('foo');
        EntityWithJitMethod::search('foo');
        EntityWithJitMethod::withConstant(42);

        // Force the cache to be saved
        EntityWithJitMethod::mapper()->destroy();
        $this->unsetPrime();
        gc_collect_cycles();

        // Reload prime
        $this->configurePrime([
            'jit' => new JitManager(
                new Psr16Cache(new FilesystemAdapter('jit', 0, $this->directory)),
                $this->directory,
                false
            ),
        ]);

        EntityWithJitMethod::repository()->schema()->migrate();
        EntityWithJitMethod::useKeyValueQuery('foo');
        EntityWithJitMethod::search('foo');
        EntityWithJitMethod::withConstant(42);

        $jitProperty = new \ReflectionProperty(Mapper::class, 'jit');
        $jitProperty->setAccessible(true);
        $jit = $jitProperty->getValue(EntityWithJitMethod::mapper());
        $hooksProperty = new \ReflectionProperty($jit, 'hooks');
        $hooksProperty->setAccessible(true);
        /** @var \Bdf\Prime\Mapper\Jit\JitQueryHook[] $hooks */
        $hooks = $hooksProperty->getValue($jit);

        $this->assertEquals(2, $hooks['useKeyValueQuery']->count);
        $this->assertEquals(2, $hooks['search']->count);
        $this->assertEquals(2, $hooks['withConstant']->count);
    }

    public function test_load_compiled_from_cache()
    {
        EntityWithJitMethod::repository()->schema()->migrate();

        EntityWithJitMethod::useKeyValueQuery('foo');
        EntityWithJitMethod::useKeyValueQuery('foo');
        EntityWithJitMethod::useKeyValueQuery('foo');
        EntityWithJitMethod::search('foo');
        EntityWithJitMethod::search('foo');
        EntityWithJitMethod::search('foo');
        EntityWithJitMethod::withConstant(42);
        EntityWithJitMethod::withConstant(42);
        EntityWithJitMethod::withConstant(42);

        // Force the cache to be saved
        EntityWithJitMethod::mapper()->destroy();
        $this->unsetPrime();
        gc_collect_cycles();

        // Reload prime
        $this->configurePrime([
            'jit' => new JitManager(
                new Psr16Cache(new FilesystemAdapter('jit', 0, $this->directory)),
                $this->directory,
                false
            ),
        ]);

        EntityWithJitMethod::repository()->schema()->migrate();
        EntityWithJitMethod::useKeyValueQuery('foo');
        EntityWithJitMethod::search('foo');
        EntityWithJitMethod::withConstant(42);

        $jitProperty = new \ReflectionProperty(Mapper::class, 'jit');
        $jitProperty->setAccessible(true);
        $jit = $jitProperty->getValue(EntityWithJitMethod::mapper());

        $hooksProperty = new \ReflectionProperty($jit, 'hooks');
        $hooksProperty->setAccessible(true);
        /** @var \Bdf\Prime\Mapper\Jit\JitQueryHook[] $hooks */
        $hooks = $hooksProperty->getValue($jit);

        $this->assertEmpty($hooks);

        $compiledProperty = new \ReflectionProperty($jit, 'compiledQueries');
        $compiledProperty->setAccessible(true);
        $compiled = $compiledProperty->getValue($jit);

        $this->assertCount(3, $compiled);
    }

    public function test_parseObjectAndMethod()
    {
        $foo = new Foo(42);

        $this->assertSame([$foo, 'bar'], $this->jit->parseObjectAndMethod([$foo, 'bar']));
        $this->assertSame([$foo, 'bar'], $this->jit->parseObjectAndMethod(\Closure::fromCallable([$foo, 'bar'])));
        $this->assertNull($this->jit->parseObjectAndMethod(static function () {}));
        $this->assertNull($this->jit->parseObjectAndMethod(function () {}));
        $this->assertNull($this->jit->parseObjectAndMethod('var_dump'));
        $this->assertNull($this->jit->parseObjectAndMethod([\Closure::class, 'fromCallable']));
    }
}

class Foo
{
    private int $a;

    /**
     * @param int $a
     */
    public function __construct(int $a)
    {
        $this->a = $a;
    }


    public function bar(int $b): int
    {
        return $this->a + $b;
    }

    public function myQuery(EntityRepository $repository, string $search): array
    {
        return $repository
            ->builder()
            ->where('first', (new Like($search))->startsWith())
            ->limit(15)
            ->all()
        ;
    }
}
