<?php

namespace Php80\Mapper\Jit;

use Bdf\Prime\Collection\EntityCollection;
use Bdf\Prime\Mapper\Jit\JitException;
use Bdf\Prime\Mapper\Jit\JitManager;
use Bdf\Prime\Mapper\Jit\MapperJit;
use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\TestEntity;
use FilesystemIterator;
use Php80\Mapper\_files\EntityWithJitMethod;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

use function is_dir;
use function rmdir;
use function unlink;

class FunctionalResultTest extends TestCase
{
    use PrimeTestCase;
    private JitManager $manager;
    private FilesystemAdapter $cache;

    protected function setUp(): void
    {
        $this->directory = '/tmp/' . uniqid('jit');
        $this->cache = new FilesystemAdapter('jit', 0, $this->directory);
        $this->manager = new JitManager(
            new Psr16Cache($this->cache),
            $this->directory,
            true,
            3,
            false
        );

        $this->configurePrime([
            'jit' => $this->manager,
        ]);

        $this->pack()
            ->declareEntity([EntityWithJitMethod::class, TestEntity::class])
            ->persist([
                new TestEntity([
                    'id' => 1,
                    'name' => 'foo',
                ]),
                new TestEntity([
                    'id' => 2,
                    'name' => 'bar',
                ]),
                new EntityWithJitMethod(1, 'baz'),
                new EntityWithJitMethod(2, 'rab'),
                new EntityWithJitMethod(42, 'rab'),
                new EntityWithJitMethod(12, 'foo'),
                new EntityWithJitMethod(14, 'bar'),
                new EntityWithJitMethod(52, 'bar'),
            ])
            ->initialize()
        ;
    }

    protected function tearDown(): void
    {
        unset($this->jit);
        unset($this->manager);

        $this->pack()->destroy();
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

    public function test_simple()
    {
        $this->runMethod(EntityWithJitMethod::class, 'search', [
            ['foo'],
            ['bar'],
            ['not_found'],
        ]);
    }

    public function test_ambiguous()
    {
        $this->runMethod(EntityWithJitMethod::class, 'ambiguous', [
            [12, 12],
            [0, 100],
            [1, 4],
        ]);
    }

    public function test_withConstant()
    {
        $this->runMethod(EntityWithJitMethod::class, 'withConstant', [
            [0],
            [10],
            [40],
        ]);
    }

    public function test_keyvalue()
    {
        $this->runMethod(EntityWithJitMethod::class, 'useKeyValueQuery', [
            ['foo'],
            ['bar'],
            ['invalid'],
        ]);
    }

    public function test_wrapper()
    {
        $this->runMethod(EntityWithJitMethod::class, 'withCustomMetadata', [
            ['foo'],
            ['bar'],
            ['invalid'],
        ]);
    }

    public function test_extension_call_by_and_with()
    {
        $this->runMethod(EntityWithJitMethod::class, 'withExtensionCalls', [
            [1, 4],
            [10, 100],
            [100, 150],
        ]);
    }

    public function test_complexMethod()
    {
        $this->runMethod(EntityWithJitMethod::class, 'complexMethod', [
            ['foo'],
            ['bar'],
        ]);
    }

    public function runMethod(string $entity, string $method, array $calls)
    {
        $repository = $this->prime()->repository($entity);
        $lastResults = [];

        for ($i = 0; $i < 10; ++$i) {
            foreach ($calls as $index => $args) {
                $result = $repository->{$method}(...$args);

                if (isset($lastResults[$index])) {
                    $this->assertEquals($lastResults[$index], $result, 'The result of method ' . $method . ' has changed');
                } else {
                    $lastResults[$index] = $result;
                }
            }
        }

        $jitProp = new \ReflectionProperty(Mapper::class, 'jit');
        $jitProp->setAccessible(true);

        $compiledProp = new \ReflectionProperty(MapperJit::class, 'compiledQueries');
        $compiledProp->setAccessible(true);

        $jit = $jitProp->getValue($repository->mapper());
        $compiled = $compiledProp->getValue($jit);

        $this->assertArrayHasKey($method, $compiled, 'The method ' . $method . ' should be compiled');
    }
}
