<?php

namespace Php80\Mapper\Jit;

use Bdf\Prime\Collection\EntityCollection;
use Bdf\Prime\Mapper\Jit\JitException;
use Bdf\Prime\Mapper\Jit\JitManager;
use Bdf\Prime\Mapper\Jit\MapperJit;
use Bdf\Prime\PrimeTestCase;
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

class FunctionalCompilationErrorTest extends TestCase
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
            true,
            3,
            false
        );

        $this->configurePrime([
            'jit' => $this->manager,
        ]);

        $this->jit = new MapperJit($this->manager, EntityWithJitMethod::repository()->mapper());
    }

    protected function tearDown(): void
    {
        unset($this->jit);
        unset($this->manager);

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

    public function test_success()
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

    public function test_should_not_compile_not_constant_query()
    {
        $this->expectException(JitException::class);
        $this->expectExceptionMessage('Error while JIT compiling method Php80\Mapper\_files\EntityWithJitMethodMapper::notConstantQuery(): The compiled query has changed');

        EntityWithJitMethod::repository()->schema()->migrate();
        EntityWithJitMethod::notConstantQuery('id', 42);
        EntityWithJitMethod::notConstantQuery('name', 'foo');
    }

    public function test_notSelectQuery()
    {
        $this->expectException(JitException::class);
        $this->expectExceptionMessage('Error while JIT compiling method Php80\Mapper\_files\EntityWithJitMethodMapper::notSelectQuery(): Cannot get the SQL of the query');

        EntityWithJitMethod::repository()->schema()->migrate();

        EntityWithJitMethod::notSelectQuery();
        EntityWithJitMethod::notSelectQuery();
    }

    public function test_changingArgumentMapping()
    {
        $this->expectException(JitException::class);
        $this->expectExceptionMessage('Error while JIT compiling method Php80\Mapper\_files\EntityWithJitMethodMapper::changingArgumentMapping(): The arguments mapping has changed');

        EntityWithJitMethod::repository()->schema()->migrate();

        EntityWithJitMethod::changingArgumentMapping(12, 14);
        EntityWithJitMethod::changingArgumentMapping(66, 15);
    }

    public function test_changing_constant()
    {
        $this->expectException(JitException::class);
        $this->expectExceptionMessage('Error while JIT compiling method Php80\Mapper\_files\EntityWithJitMethodMapper::constantNotSoConstant(): The constants has changed');

        EntityWithJitMethod::repository()->schema()->migrate();

        EntityWithJitMethod::constantNotSoConstant();
        EntityWithJitMethod::constantNotSoConstant();
    }

    public function test_missingRepositoryParameter()
    {
        $this->expectException(JitException::class);
        $this->expectExceptionMessage('Error while JIT compiling method Php80\Mapper\_files\EntityWithJitMethodMapper::missingRepositoryParameter2(): The method should have at least the repository as first parameter');

        EntityWithJitMethod::repository()->schema()->migrate();

        EntityWithJitMethod::missingRepositoryParameter2();
        EntityWithJitMethod::missingRepositoryParameter2();
        EntityWithJitMethod::missingRepositoryParameter2();
    }

    public function test_withNotConstantExtensionCall()
    {
        $this->expectException(JitException::class);
        $this->expectExceptionMessage('Error while JIT compiling method Php80\Mapper\_files\EntityWithJitMethodMapper::withNotConstantExtensionCall(): The extension parameters has changed');

        EntityWithJitMethod::repository()->schema()->migrate();

        EntityWithJitMethod::withNotConstantExtensionCall();
        EntityWithJitMethod::withNotConstantExtensionCall();
        EntityWithJitMethod::withNotConstantExtensionCall();
    }

    public function test_metadataMappingChange()
    {
        $this->expectException(JitException::class);
        $this->expectExceptionMessage('Error while JIT compiling method Php80\Mapper\_files\EntityWithJitMethodMapper::metadataMappingChange(): The metadata mapping has changed');

        EntityWithJitMethod::repository()->schema()->migrate();

        EntityWithJitMethod::metadataMappingChange(EntityCollection::class);
        EntityWithJitMethod::metadataMappingChange('');
    }

    public function test_constantMetadataChange()
    {
        $this->expectException(JitException::class);
        $this->expectExceptionMessage('Error while JIT compiling method Php80\Mapper\_files\EntityWithJitMethodMapper::constantMetadataChange(): The metadata constants has changed');

        EntityWithJitMethod::repository()->schema()->migrate();

        EntityWithJitMethod::constantMetadataChange('foo');
        EntityWithJitMethod::constantMetadataChange('bar');
    }
}
