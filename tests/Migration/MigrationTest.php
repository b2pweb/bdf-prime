<?php

namespace Bdf\Prime\Migration;

use Bdf\Prime\Prime;
use Composer\Autoload\ClassLoader;
use Composer\Composer;
use Composer\InstalledVersions;
use Composer\Installer;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 *
 */
class MigrationTest extends TestCase
{
    /**
     * @var Migration
     */
    protected $migration;
    
    /**
     * 
     */
    protected function setUp(): void
    {
        $this->migration = new Migration(
            '123456789',
            new Container([
                'prime' => Prime::service()
            ])
        );
    }
    
    /**
     * 
     */
    public function test_empty_methods()
    {
        $this->assertNull($this->migration->initialize());
        $this->assertNull($this->migration->up());
        $this->assertNull($this->migration->down());
    }
    
    /**
     * 
     */
    public function test_get_name()
    {
        $this->assertEquals(Migration::class, $this->migration->name());
    }
    
    /**
     * 
     */
    public function test_get_version()
    {
        $this->assertEquals('123456789', $this->migration->version());
    }
    
    /**
     * 
     */
    public function test_schema()
    {
        $this->assertInstanceOf('Bdf\Prime\Schema\SchemaManager', $this->migration->schema('test'));
    }
    
    /**
     * 
     */
    public function test_query()
    {
        Prime::create('Bdf\Prime\TestEntity');
        Prime::push('Bdf\Prime\TestEntity', [
            'id'   => 1,
            'name' => 'test-name'
        ]);
        
        $entity = $this->migration->query('select * from test_ where id = :id', ['id' => 1], 'test')->fetchAll()[0];
        
        $this->assertEquals(1, $entity['id']);
        $this->assertEquals('test-name', $entity['name']);
        
        Prime::drop('Bdf\Prime\TestEntity');
    }
    
    /**
     * 
     */
    public function test_update()
    {
        Prime::create('Bdf\Prime\TestEntity');
        Prime::push('Bdf\Prime\TestEntity', [
            'id'   => 1,
            'name' => 'test-name'
        ]);
        
        $this->migration->update('update test_ set name = :name', ['name' => 'new'], 'test');
        $entity = $this->migration->query('select * from test_ where id = :id', ['id' => 1], 'test')->fetchAll()[0];
        
        $this->assertEquals('new', $entity['name']);
        
        Prime::drop('Bdf\Prime\TestEntity');
    }
}

if ((new \ReflectionMethod(ContainerInterface::class, 'has'))->getReturnType() !== null) {
    // psr/container v2
    class Container implements ContainerInterface
    {
        private $service;

        public function __construct(array $service)
        {
            $this->service = $service;
        }

        public function get(string $id)
        {
            return $this->service[$id] ?? null;
        }

        public function has(string $id): bool
        {
            return isset($this->service[$id]);
        }
    }
} elseif ((new \ReflectionMethod(ContainerInterface::class, 'has'))->getParameters()[0]->getType() !== null) {
    // psr/container v1.1
    class Container implements ContainerInterface
    {
        private $service;

        public function __construct(array $service)
        {
            $this->service = $service;
        }

        public function get(string $id)
        {
            return $this->service[$id] ?? null;
        }

        public function has(string $id)
        {
            return isset($this->service[$id]);
        }
    }
} else {
    // psr/container v1.0
    class Container implements ContainerInterface
    {
        private $service;

        public function __construct(array $service)
        {
            $this->service = $service;
        }

        public function get($id)
        {
            return $this->service[$id] ?? null;
        }

        public function has($id)
        {
            return isset($this->service[$id]);
        }
    }
}
