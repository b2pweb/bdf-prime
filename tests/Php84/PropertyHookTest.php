<?php

namespace Php84;

use Bdf\Prime\Bench\HydratorGeneration;
use Bdf\Prime\PrimeTestCase;
use Bdf\Prime\Test\TestPack;
use Php84\Fixtures\EntityWithAsymmetricVisibility;
use Php84\Fixtures\EntityWithGetterProperty;
use Php84\Fixtures\EntityWithPropertyHook;
use PHPUnit\Framework\TestCase;

class PropertyHookTest extends TestCase
{
    use PrimeTestCase;

    protected function setUp(): void
    {
        $this->primeStart();
        TestPack::pack()->declareEntity([EntityWithPropertyHook::class, EntityWithGetterProperty::class, EntityWithAsymmetricVisibility::class]);
    }

    protected function tearDown(): void
    {
        $this->unsetPrime();
    }

    public function test_functional_read_write_property()
    {
        $entity = new EntityWithPropertyHook(
            name: 'Roger',
            ownerId: 1,
            stats: [
                EntityWithPropertyHook::STATS_STRENGTH => 10,
                EntityWithPropertyHook::STATS_INTELLIGENCE => 20,
                EntityWithPropertyHook::STATS_AGILITY => 30,
            ]
        );

        $entity->save();

        $refresh = EntityWithPropertyHook::refresh($entity);

        $this->assertSame('Roger', $refresh->name);
        $this->assertSame(10, $refresh->strength);
        $this->assertSame(20, $refresh->intelligence);
        $this->assertSame(30, $refresh->agility);
        $this->assertSame([
            EntityWithPropertyHook::STATS_STRENGTH => 10,
            EntityWithPropertyHook::STATS_INTELLIGENCE => 20,
            EntityWithPropertyHook::STATS_AGILITY => 30,
        ], $refresh->stats);
    }

    public function test_functional_getter_property()
    {
        $entity = new EntityWithGetterProperty(
            email: 'foo+bar@example.com',
            password: 'password'
        );

        $entity->save();

        $refresh = EntityWithGetterProperty::refresh($entity);

        $this->assertSame('foo+bar@example.com', $refresh->email);
        $this->assertSame('password', $refresh->password);
        $this->assertSame('foo@example.com', $refresh->normalizedEmail);

        $this->assertSame([[
            'id' => 1,
            'email' => 'foo+bar@example.com',
            'password' => 'password',
            'normalized_email' => 'foo@example.com',
        ]], EntityWithGetterProperty::builder()->where('id', 1)->execute()->all());
    }

    public function test_functional_magic_relation_getter()
    {
        $entity = new EntityWithGetterProperty(
            email: 'foo+bar@example.com',
            password: 'password'
        );
        $entity->save();

        TestPack::pack()->nonPersist([
            $r1 = new EntityWithPropertyHook(
                name: 'Roger',
                ownerId: 1,
                stats: [1, 2, 3]
            ),
            $r2 = new EntityWithPropertyHook(
                name: 'Jean',
                ownerId: 1,
                stats: [1, 2, 3]
            ),
        ]);

        $this->assertEquals([$r1, $r2], $entity->characters);
    }

    public function test_functional_with_asymmetric_visibility()
    {
        $entity = new EntityWithAsymmetricVisibility(name: 'Robert');
        $entity->save();

        $refresh = EntityWithAsymmetricVisibility::refresh($entity);

        $this->assertSame(1, $refresh->id);
        $this->assertSame('Robert', $refresh->name);
    }
}
