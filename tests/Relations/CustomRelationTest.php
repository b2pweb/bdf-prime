<?php

namespace Bdf\Prime\Relations;

use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Test\RepositoryAssertion;
use Bdf\Prime\Test\TestPack;
use PHPUnit\Framework\TestCase;

class CustomRelationTest extends TestCase
{
    use PrimeTestCase;
    use RepositoryAssertion;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->primeStart();
    }

    /**
     *
     */
    public function declareTestData(TestPack $pack)
    {
        $pack->declareEntity([EntityWithCustomRelation::class, DistantEntityForCustomRelation::class]);
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        $this->primeStop();
    }

    /**
     *
     */
    public function test_load()
    {
        $this->pack()->nonPersist([
            $entity = new EntityWithCustomRelation([
                'key1' => '123',
                'key2' => '456',
                'value' => 'Hello'
            ]),
            $distant = new DistantEntityForCustomRelation([
                'key1' => '123',
                'key2' => '456',
                'value' => 'World'
            ])
        ]);

        $entity->load('distant');

        $this->assertEquals($distant, $entity->distant);
        $this->assertTrue($entity->relation('distant')->isLoaded());
    }

    /**
     *
     */
    public function test_load_not_found()
    {
        $this->pack()->nonPersist([
            $entity = new EntityWithCustomRelation([
                'key1' => '123',
                'key2' => '456',
                'value' => 'Hello'
            ]),
            $distant = new DistantEntityForCustomRelation([
                'key1' => '789',
                'key2' => '456',
                'value' => 'World'
            ])
        ]);

        $entity->load('distant');

        $this->assertNull($entity->distant);
        $this->assertFalse($entity->relation('distant')->isLoaded());
    }

    /**
     *
     */
    public function test_filter_on_relation()
    {
        $this->pack()->nonPersist([
            $entity = new EntityWithCustomRelation([
                'key1' => '123',
                'key2' => '456',
                'value' => 'Hello'
            ]),
            $distant = new DistantEntityForCustomRelation([
                'key1' => '123',
                'key2' => '456',
                'value' => 'World'
            ])
        ]);

        $this->assertEquals($entity, EntityWithCustomRelation::where('distant.value', 'World')->first());
        $this->assertNull(EntityWithCustomRelation::where('distant.value', 'Not found')->first());
    }

    /**
     *
     */
    public function test_with()
    {
        $this->pack()->nonPersist([
            new EntityWithCustomRelation([
                'key1' => '123',
                'key2' => '456',
                'value' => 'Hello'
            ]),
            new EntityWithCustomRelation([
                'key1' => 'az',
                'key2' => 'er',
                'value' => 'aqw'
            ]),
            new EntityWithCustomRelation([
                'key1' => 'ty',
                'key2' => 'ui',
                'value' => 'zsx'
            ]),
            $dist1 = new DistantEntityForCustomRelation([
                'key1' => '123',
                'key2' => '456',
                'value' => 'World'
            ]),
            $dist2 = new DistantEntityForCustomRelation([
                'key1' => 'ty',
                'key2' => 'ui',
                'value' => 'ijn'
            ]),
        ]);

        $entities = EntityWithCustomRelation::with('distant')->all();

        $this->assertCount(3, $entities);
        $this->assertContainsOnlyInstancesOf(EntityWithCustomRelation::class, $entities);

        $this->assertEquals($dist1, $entities[0]->distant);
        $this->assertNull($entities[1]->distant);
        $this->assertEquals($dist2, $entities[2]->distant);
    }

    /**
     *
     */
    public function test_query_on_relation()
    {
        $this->pack()->nonPersist([
            $entity = new EntityWithCustomRelation([
                'key1' => '123',
                'key2' => '456',
                'value' => 'Hello'
            ]),
            $distant = new DistantEntityForCustomRelation([
                'key1' => '123',
                'key2' => '456',
                'value' => 'World'
            ])
        ]);

        $this->assertEquals($distant, $entity->relation('distant')->first());
        $this->assertEquals($distant, $entity->relation('distant')->where('value', '>', 'A')->first());
        $this->assertNull($entity->relation('distant')->where('value', '<', 'A')->first());
    }

    /**
     *
     */
    public function test_foreignIn()
    {
        $this->pack()->nonPersist([
            $owner = new EntityForeignInOwner([
                'fk1' => 1,
                'fk2' => 2,
            ]),
            $owner2 = new EntityForeignInOwner([
                'fk1' => 1,
                'fk2' => 3,
            ]),
            $r1 = new EntityForeignIn([
                'id' => 1,
                'value' => 'Hello'
            ]),
            $r2 = new EntityForeignIn([
                'id' => 2,
                'value' => 'World'
            ]),
            $r3 = new EntityForeignIn([
                'id' => 3,
                'value' => '...'
            ])
        ]);

        $this->assertEquals([$r1, $r2], $owner->relation('relation')->all());
        $this->assertEquals($r2, $owner->relation('relation')->get(2));
        $this->assertNull($owner->relation('relation')->get(3));
        $this->assertEquals([$r1], $owner->relation('relation')->where('value', 'Hello')->all());

        $this->assertEquals([$owner, $owner2], EntityForeignInOwner::where('relation.value', 'Hello')->all());
        $this->assertEquals([$owner], EntityForeignInOwner::where('relation.value', 'World')->all());
        $this->assertEquals([$owner2], EntityForeignInOwner::where('relation.value', '...')->all());

        $entities = EntityForeignInOwner::with('relation')->all();

        $this->assertEquals([$r1, $r2], $entities[0]->relation);
        $this->assertEquals([$r1, $r3], $entities[1]->relation);
    }
}
