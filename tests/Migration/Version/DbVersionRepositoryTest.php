<?php

namespace Bdf\Prime\Migration\Version;

use Bdf\Prime\Prime;
use Bdf\Prime\PrimeTestCase;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class DbVersionRepositoryTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var DbVersionRepository
     */
    protected $repository;
    
    /**
     * 
     */
    protected function setUp(): void
    {
        $this->configurePrime();

        $this->repository = new DbVersionRepository(Prime::connection('test'), 'migration');
    }

    protected function tearDown(): void
    {
        $this->unsetPrime();
    }

    /**
     * 
     */
    public function test_get_table()
    {
        $this->assertEquals('migration', $this->repository->getTable());
    }
    
    /**
     * 
     */
    public function test_get_connection()
    {
        $this->assertSame(Prime::connection('test'), $this->repository->getConnection());
    }
    
    /**
     * 
     */
    public function test_schema_management()
    {
        $this->assertFalse($this->repository->hasSchema());
        $this->repository->createSchema();
        $this->assertTrue($this->repository->hasSchema());
    }
    
    /**
     * 
     */
    public function test_version_management()
    {
        $version = '123456789';

        $this->assertEquals([], $this->repository->all());

        $this->repository->add($version);
        $this->assertEquals([$version], $this->repository->all());
        
        $this->repository->remove($version);
        $this->assertEquals([], $this->repository->all());
    }
}
