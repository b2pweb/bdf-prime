<?php

namespace Php84;

use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Test\TestPack;
use Php84\Fixtures\EntityWithGetterProperty;
use Php84\Fixtures\EntityWithPropertyHook;
use PHPUnit\Framework\TestCase;

class ProxyTest extends TestCase
{
    use PrimeTestCase;

    protected function setUp(): void
    {
        $this->primeStart();
        TestPack::pack()->declareEntity([EntityWithPropertyHook::class, EntityWithGetterProperty::class]);
    }

    protected function tearDown(): void
    {
        $this->unsetPrime();
    }

    public function test_functional_proxy_relation()
    {
        $entity = new EntityWithGetterProperty(
            email: 'foo+bar@example.com',
            password: 'password'
        );
        $entity->save();

        TestPack::pack()->nonPersist([
            $r1 = new EntityWithPropertyHook(
                name: 'Roger',
                ownerId: $entity->id,
                stats: [1, 2, 3]
            ),
            $r2 = new EntityWithPropertyHook(
                name: 'Jean',
                ownerId: $entity->id,
                stats: [1, 2, 3]
            ),
        ]);

        $this->assertInstanceOf(EntityWithGetterProperty::class, $r1->owner);
        $this->assertNotEquals($entity, $r1->owner); // Proxy is not loaded
        $this->assertEquals('foo+bar@example.com', $r1->owner->email); // Trigger the proxy loading
        $this->assertEquals($entity, $r1->owner);
    }

    public function test_relation_proxy()
    {
        $relation = new EntityWithGetterProperty(
            email: 'foo+bar@example.com',
            password: 'password'
        );
        $relation->save();

        $entity = new EntityWithPropertyHook(
            name: 'Roger',
            ownerId: $relation->id,
            stats: [1, 2, 3]
        );
        $entity->save();

        $proxy = $entity->relation('owner')->proxy();

        $this->assertInstanceOf(EntityWithGetterProperty::class, $proxy);
        $this->assertTrue((new \ReflectionClass($proxy))->isUninitializedLazyObject($proxy));

        $this->assertEquals('foo+bar@example.com', $proxy->email); // Trigger the proxy loading
        $this->assertEquals($relation, $proxy);
    }
}
