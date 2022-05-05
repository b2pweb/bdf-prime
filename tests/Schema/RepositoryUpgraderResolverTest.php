<?php

namespace Schema;

use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Schema\RepositoryUpgraderResolver;
use Bdf\Prime\Schema\RepositoryUpgrader;
use Bdf\Prime\TestEntity;
use Bdf\Prime\TestEntityMapper;
use PHPUnit\Framework\TestCase;

class RepositoryUpgraderResolverTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var RepositoryUpgraderResolver
     */
    protected $resolver;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();

        $this->resolver = new RepositoryUpgraderResolver($this->prime());
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->primeStop();
    }

    public function test_resolveByMapperClass()
    {
        $this->assertNull($this->resolver->resolveByMapperClass(\ArrayObject::class));
        $this->assertNull($this->resolver->resolveByMapperClass(TestEntity::class));
        $this->assertInstanceOf(RepositoryUpgrader::class, $this->resolver->resolveByMapperClass(TestEntityMapper::class));
        $this->assertSame('test_', $this->resolver->resolveByMapperClass(TestEntityMapper::class)->table()->name());
    }

    public function test_resolveByDomainClass()
    {
        $this->assertNull($this->resolver->resolveByDomainClass(\ArrayObject::class));
        $this->assertNull($this->resolver->resolveByDomainClass(TestEntityMapper::class));
        $this->assertInstanceOf(RepositoryUpgrader::class, $this->resolver->resolveByDomainClass(TestEntity::class));
        $this->assertSame('test_', $this->resolver->resolveByDomainClass(TestEntity::class)->table()->name());
    }
}
