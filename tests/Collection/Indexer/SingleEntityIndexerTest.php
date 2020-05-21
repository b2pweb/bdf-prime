<?php

namespace Bdf\Prime\Collection\Indexer;

use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\TestEntity;
use PHPUnit\Framework\TestCase;

/**
 * Class SingleEntityIndexerTest
 */
class SingleEntityIndexerTest extends TestCase
{
    use PrimeTestCase;

    protected function setUp(): void
    {
        $this->configurePrime();
    }

    /**
     *
     */
    public function test_values()
    {
        $entity = new TestEntity([
            'id' => 12,
            'name' => 'Foo'
        ]);

        $indexer = new SingleEntityIndexer(TestEntity::mapper(), $entity);

        $this->assertSame([12 => [$entity]], $indexer->by('id'));
        $this->assertSame(['Foo' => [$entity]], $indexer->by('name'));
        $this->assertSame([12 => $entity], $indexer->byOverride('id'));
        $this->assertSame([$entity], $indexer->all());
        $this->assertFalse($indexer->empty());
    }
}
