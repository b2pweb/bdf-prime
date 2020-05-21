<?php

namespace Bdf\Prime\Collection\Indexer;

use Bdf\Prime\Mapper\Mapper;
use Bdf\Prime\TestEntity;
use Bdf\Prime\PrimeTestCase;
use PHPUnit\Framework\TestCase;

/**
 * Class EntityIndexerTest
 */
class EntityIndexerTest extends TestCase
{
    use PrimeTestCase;

    /**
     * @var TestEntity[]
     */
    private $entities;

    /**
     * @var Mapper
     */
    private $mapper;

    protected function setUp(): void
    {
        $this->configurePrime();

        $this->entities = [
            new TestEntity(['id' => 12, 'name' => 'Foo']),
            new TestEntity(['id' => 23, 'name' => 'Bar']),
            new TestEntity(['id' => 42, 'name' => 'Foo']),
        ];

        $this->mapper = TestEntity::mapper();
    }

    /**
     *
     */
    public function test_fromArray()
    {
        $indexer = EntityIndexer::fromArray(TestEntity::mapper(), $this->entities);

        $this->assertSame($this->entities, $indexer->all());
        $this->assertFalse($indexer->empty());
    }

    /**
     *
     */
    public function test_push()
    {
        $indexer = new EntityIndexer($this->mapper);

        $this->assertTrue($indexer->empty());

        $indexer->push($this->entities[0]);
        $this->assertFalse($indexer->empty());
        $this->assertSame([$this->entities[0]], $indexer->all());

        $indexer->push($this->entities[1]);
        $indexer->push($this->entities[2]);
        $this->assertSame($this->entities, $indexer->all());
    }

    /**
     *
     */
    public function test_empty()
    {
        $indexer = new EntityIndexer($this->mapper);

        $this->assertTrue($indexer->empty());
        $this->assertSame([], $indexer->all());
        $this->assertSame([], $indexer->by('id'));
        $this->assertSame([], $indexer->byOverride('id'));
    }

    /**
     *
     */
    public function test_by()
    {
        $indexer = EntityIndexer::fromArray($this->mapper, $this->entities);

        $this->assertSame([
            'Foo' => [$this->entities[0], $this->entities[2]],
            'Bar' => [$this->entities[1]],
        ], $indexer->by('name'));

        $this->assertSame([
            12 => [$this->entities[0]],
            23 => [$this->entities[1]],
            42 => [$this->entities[2]],
        ], $indexer->by('id'));

        $this->assertSame($indexer->by('id'), $indexer->by('id'));
    }

    /**
     *
     */
    public function test_byOverride()
    {
        $indexer = EntityIndexer::fromArray($this->mapper, $this->entities);

        $this->assertSame([
            'Foo' => $this->entities[2],
            'Bar' => $this->entities[1],
        ], $indexer->byOverride('name'));

        $this->assertSame([
            12 => $this->entities[0],
            23 => $this->entities[1],
            42 => $this->entities[2],
        ], $indexer->byOverride('id'));

        $this->assertSame($indexer->byOverride('id'), $indexer->byOverride('id'));
    }

    /**
     *
     */
    public function test_push_already_indexed_should_update_index()
    {
        $indexer = new EntityIndexer($this->mapper, ['id', 'name']);

        $indexer->push($this->entities[0]);
        $indexer->push($this->entities[1]);

        $this->assertSame([
            'Foo' => [$this->entities[0]],
            'Bar' => [$this->entities[1]],
        ], $indexer->by('name'));

        $this->assertSame([
            12 => [$this->entities[0]],
            23 => [$this->entities[1]],
        ], $indexer->by('id'));

        $indexer->push($this->entities[2]);

        $this->assertSame([
            'Foo' => [$this->entities[0], $this->entities[2]],
            'Bar' => [$this->entities[1]],
        ], $indexer->by('name'));

        $this->assertSame([
            12 => [$this->entities[0]],
            23 => [$this->entities[1]],
            42 => [$this->entities[2]],
        ], $indexer->by('id'));
    }
}
